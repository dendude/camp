<?php

namespace app\models;

use app\components\SmtpEmail;
use app\helpers\Normalize;
use app\helpers\Statuses;
use app\models\forms\UploadForm;
use app\models\queries\ChangesQuery;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * This is the model class for table "{{%changes}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $partner_id
 * @property integer $camp_id
 * @property string $params
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 *
 * @property Camps $camp
 */
class Changes extends ActiveRecord
{
    public $old_attributes = [];
    public $new_attributes = [];
    
    public static function tableName()
    {
        return '{{%changes}}';
    }

    public function rules()
    {
        return [
            [['partner_id', 'camp_id', 'created', 'params'], 'required'],
            
            [['manager_id', 'partner_id', 'camp_id', 'created', 'modified', 'status'], 'integer'],
            [['manager_id', 'partner_id', 'camp_id', 'created', 'modified', 'status'], 'default', 'value' => 0],
            
            [['params'], 'string'],

            [['old_attributes', 'new_attributes'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'params' => 'Params',
        ]);
    }
    
    public function getCamp()
    {
        return $this->hasOne(Camps::className(), ['id' => 'camp_id']);
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
    
            $attr = [];
            foreach ($this->old_attributes AS $section => $params) {
                
                foreach ($params AS $k => $v) {
                    if (in_array($k, ['id', 'manager_id', 'camp_id', 'partner_id', 'created', 'modified', 'status'])) {
                        unset($this->old_attributes[$section][$k]);
                        unset($this->new_attributes[$section][$k]);
                        continue;
                    }

                    // сравниваем без пробелов и тегов
                    $old_value = preg_replace('/\s/', '', strip_tags($this->old_attributes[$section][$k]));
                    $new_value = preg_replace('/\s/', '', strip_tags($this->new_attributes[$section][$k]));

                    if ($old_value != $new_value) {
                        // если разные - сохраняем читаемые значения
                        $this->old_attributes[$section][$k] = trim($this->old_attributes[$section][$k]);
                        $this->new_attributes[$section][$k] = trim($this->new_attributes[$section][$k]);
                    }
                }
        
                $diff = array_diff_assoc($this->old_attributes[$section], $this->new_attributes[$section]);
                if (empty($diff)) {
                    $diff = array_diff_assoc($this->new_attributes[$section], $this->old_attributes[$section]);
                }
    
                foreach ($diff AS $k => $v) {
                    if (in_array($k, ['id', 'manager_id', 'camp_id', 'partner_id', 'created', 'modified', 'status'])) {
                        unset($diff[$k]);
                    }
                }
                
                if (count($diff)) {
                    foreach ($diff AS $dk => $dv) {
                        $attr[$section][$dk]['old'] = $this->old_attributes[$section][$dk];
                        $attr[$section][$dk]['new'] = $this->new_attributes[$section][$dk];
                    }
                }
            }
            
            if (count($attr)) {
                $this->params = Json::encode($attr);
            }
            
        } else {
            $this->modified = time();
            $this->manager_id = Yii::$app->user->id;
        }
        
        return parent::beforeValidate();
    }
    
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        $settings = Settings::lastSettings();
        $emails = Normalize::emailsStrToArr($settings->emails_edit_camp);
        
        if (!$emails) return;
        
        $smtp = new SmtpEmail();
        $camp = Camps::findOne($this->camp_id);
        
        foreach ($emails AS $email) {
            $u = Users::find()->where(['email' => $email])->one();
            $name = $u ? $u->first_name : 'Менеджер';
    
            $smtp->sendEmailByType(SmtpEmail::TYPE_EDIT_CAMP_NOTIFY, $email, $name, [
                '{camp-url}' => Html::a($camp->about->name_short, $camp->getCampUrl(true), ['target' => '_blank']),
                '{status}' => Statuses::getName($camp->status, Statuses::TYPE_CAMP),
                '{diff-list}' => $this->getDiffList(),
            ]);
        }
    }
    
    public function getDiffList()
    {
        if (!$this->params) return null;
        
        $params = Json::decode($this->params, true);
        
        $camp = Camps::findOne($this->camp_id);
        
        $base_periods = new BasePeriods();
        $base_placements = new BasePlacements();
        $base_items = new BaseItems();
        
        $title_options = ['colspan' => 3, 'style' => 'font-weight: bold; font-size: 80%; color: #999'];
    
        $rows = [
            [
                Html::tag('th', 'Название параметра', ['style' => 'font-weight: bold']),
                Html::tag('th', 'Старое значение', ['style' => 'font-weight: bold']),
                Html::tag('th', 'Новое значение', ['style' => 'font-weight: bold']),
            ]
        ];
        
        foreach ($params AS $section => $data) {
            
            foreach ($data AS $k => $v) {
    
                switch (true) {
                    case ($section == 'about'):
                        if (!isset($rows['about'])) {
                            $rows['about'] = [Html::tag('td', 'О лагере', $title_options)];
                        }
                        
                        if (in_array($k, ['tags_types', 'tags_sport', 'tags_places', 'tags_services'])) {
                            $old = explode(',', trim($params[$section][$k]['old'], ','));
                            $new = explode(',', trim($params[$section][$k]['new'], ','));
                            
                            $old_arr = [];
                            $new_arr = [];
                            
                            switch ($k) {
                                case 'tags_types':      $CLASS_NAME = 'app\models\TagsTypes';      break;
                                case 'tags_sport':      $CLASS_NAME = 'app\models\TagsSport';      break;
                                case 'tags_places':     $CLASS_NAME = 'app\models\TagsPlaces';     break;
                                case 'tags_services':   $CLASS_NAME = 'app\models\ComfortTypes';   break;
                            }
                            
                            if (count($old)) foreach ($old AS $v) {$m = $CLASS_NAME::findOne($v); if ($m) $old_arr[] = $m->title;}
                            if (count($new)) foreach ($new AS $v) {$m = $CLASS_NAME::findOne($v); if ($m) $new_arr[] = $m->title;}
                            
                            if ($old_arr == $new_arr) break;
    
                            $rows[] = [
                                Html::tag('td', $camp->{$section}->getAttributeLabel($k)),
                                Html::tag('td', implode(', ', $old_arr)),
                                Html::tag('td', implode(', ', $new_arr)),
                            ];
                        } else {
                            $rows[] = [
                                Html::tag('td', $camp->{$section}->getAttributeLabel($k)),
                                Html::tag('td', $params[$section][$k]['old']),
                                Html::tag('td', $params[$section][$k]['new']),
                            ];
                        }
                        break;
                        
                    case ($section == 'placement'):
                        if (!isset($rows['placement'])) {
                            $rows['placement'] = [Html::tag('td', 'Размещение', $title_options)];
                        }
                        $rows[] = [
                            Html::tag('td', $camp->{$section}->getAttributeLabel($k)),
                            Html::tag('td', $params[$section][$k]['old']),
                            Html::tag('td', $params[$section][$k]['new']),
                        ];
                        break;
    
                    case ($section == 'media'):
                        if (!isset($rows['media'])) {
                            $rows['media'] = [Html::tag('td', 'Фото и видео', $title_options)];
                        }
                        
                        if ($k == 'videos') {
                            $vo_arr = [];
                            $vn_arr = [];
                            
                            if ($params[$section][$k]['old']) {
                                $vo = Json::decode($params[$section][$k]['old'], true);
                                foreach ($vo AS $vo_item) $vo_arr[] = Normalize::getVideoSrc($vo_item);
                            }
                            
                            if ($params[$section][$k]['new']) {
                                $vn = Json::decode($params[$section][$k]['new'], true);
                                foreach ($vn AS $vn_item) $vn_arr[] = Normalize::getVideoSrc($vn_item);
                            }
                            
                            $rows[] = [
                                Html::tag('td', $camp->{$section}->getAttributeLabel($k)),
                                Html::tag('td', implode('<br/>', $vo_arr)),
                                Html::tag('td', implode('<br/>', $vn_arr)),
                            ];
                        } else {
                            $po_arr = [];
                            $pn_arr = [];
    
                            if ($params[$section][$k]['old']) {
                                try {
                                    // json-массив значений
                                    $po = Json::decode($params[$section][$k]['old'], true);
                                } catch (\Exception $e) {
                                    // строка
                                    $po = [$params[$section][$k]['old']];
                                }
                                
                                foreach ($po AS $po_item) {
                                    $po_arr[] = Html::img(Yii::$app->request->hostInfo . UploadForm::getSrc($po_item, UploadForm::TYPE_CAMP, '_sm'), [
                                        'height' => 50,
                                        'vspace' => 2,
                                        'hspace' => 2,
                                    ]);
                                }
                            }
    
                            if ($params[$section][$k]['new']) {
                                try {
                                    // json-массив значений
                                    $pn = Json::decode($params[$section][$k]['new'], true);
                                } catch (\Exception $e) {
                                    // строка
                                    $pn = [$params[$section][$k]['new']];
                                }
                                
                                foreach ($pn AS $pn_item) {
                                    $pn_arr[] = Html::img(Yii::$app->request->hostInfo . UploadForm::getSrc($pn_item, UploadForm::TYPE_CAMP, '_sm'), [
                                        'height' => 50,
                                        'vspace' => 2,
                                        'hspace' => 2,
                                    ]);
                                }
                            }
                            
                            $rows[] = [
                                Html::tag('td', $camp->{$section}->getAttributeLabel($k)),
                                Html::tag('td', implode(' ', $po_arr)),
                                Html::tag('td', implode(' ', $pn_arr)),
                            ];
                        }
                        break;
                    
                    case ($section == 'client'):
                        if (!isset($rows['client'])) {
                            $rows['client'] = [Html::tag('td', 'Дополнительно', $title_options)];
                        }
                        $rows[] = [
                            Html::tag('td', $camp->{$section}->getAttributeLabel($k)),
                            Html::tag('td', $params[$section][$k]['old']),
                            Html::tag('td', $params[$section][$k]['new']),
                        ];
                        break;
                    
                    case ($section == 'contacts'):
                        if (!isset($rows['contacts'])) {
                            $rows['contacts'] = [Html::tag('td', 'Контакты', $title_options)];
                        }
                        $rows[] = [
                            Html::tag('td', $camp->{$section}->getAttributeLabel($k)),
                            Html::tag('td', $params[$section][$k]['old']),
                            Html::tag('td', $params[$section][$k]['new']),
                        ];
                        break;
                    
                    case ($section == 'contract'):
                        if (!isset($rows['contract'])) {
                            $rows['contract'] = [ Html::tag('td', 'Договор', $title_options) ];
                        }
                        $rows[] = [
                            Html::tag('td', $camp->{$section}->getAttributeLabel($k)),
                            Html::tag('td', $params[$section][$k]['old']),
                            Html::tag('td', $params[$section][$k]['new']),
                        ];
                        break;
        
                    case (strpos($section, 'base_placement_') === 0):
                        if (!isset($rows['base_placements'])) {
                            $rows['base_placements'] = [Html::tag('td', 'Варианты размещения', $title_options)];
                        }
                        
                        if ($k == 'comfort_type') {
                            $ro = isset($params[$section][$k]['old']) ? $camp->placement->getPlacementName($params[$section][$k]['old']) : '';
                            $rn = isset($params[$section][$k]['new']) ? $camp->placement->getPlacementName($params[$section][$k]['new']) : 'удалено';
                        } else {
                            $ro = isset($params[$section][$k]['old']) ? $params[$section][$k]['old'] : '';
                            $rn = isset($params[$section][$k]['new']) ? $params[$section][$k]['new'] : 'удалено';
                        }
                        
                        $rows[] = [
                            Html::tag('td', $base_placements->getAttributeLabel($k)),
                            Html::tag('td', $ro),
                            Html::tag('td', $rn),
                        ];
                        break;
        
                    case (strpos($section, 'base_periods_') === 0):
                        if (!isset($rows['base_periods'])) {
                            $rows['base_periods'] = [Html::tag('td', 'Периоды сезонности', $title_options)];
                        }
                        
                        // пропускаем только dd.mm.yyyy
                        if (strpos($k, '_orig') === false) break;
                        
                        $rows[] = [
                            Html::tag('td', $base_periods->getAttributeLabel($k)),
                            Html::tag('td', isset($params[$section][$k]['old']) ? $params[$section][$k]['old'] : ''),
                            Html::tag('td', isset($params[$section][$k]['new']) ? $params[$section][$k]['new'] : 'удалено'),
                        ];
                        break;
        
                    case (strpos($section, 'base_items_') === 0):
                        if (!isset($rows['base_items'])) {
                            $rows['base_items'] = [Html::tag('td', 'Смены', $title_options)];
                        }
    
                        // пропускаем только dd.mm.yyyy
                        if ($k == 'date_from' || $k == 'date_to' ||
                            $k == 'comission_type' || $k == 'discount_type' ||
                            $k == 'comission_value' || $k == 'discount_value') break;
    
                        if (!isset($rows[$section])) {
                            if (preg_match('/base_items_(\d+)$/i', $section, $matches)) {
                                // редактируемая запись по ид
                                $item = BaseItems::findOne($matches['1']);
                                $rows[$section] = [Html::tag('td', ' -- ' . $item->name_short, $title_options)];
                            } else {
                                // новая запись по рандомной строке
                                $rows[$section] = [Html::tag('td', ' -- ' . $params[$section]['name_short']['new'] . ' [новая запись]', $title_options)];
                            }
                        }
                        
                        $rows[] = [
                            Html::tag('td', $base_items->getAttributeLabel($k)),
                            Html::tag('td', isset($params[$section][$k]['old']) ? $params[$section][$k]['old'] : ''),
                            Html::tag('td', isset($params[$section][$k]['new']) ? $params[$section][$k]['new'] : 'удалено'),
                        ];
                        break;
                }
            }
        }
        
        $result = '';
        foreach ($rows AS $row) {
            $result.= Html::tag('tr', implode('', $row));
        }
        
        return Html::tag('table', $result, [
            'width' => '100%',
            'cellpadding' => 3,
            'cellspacing' => 0,
            'border' => 1,
            'bordercolor' => '#CCC',
            'class' => 'table table-condensed table-hover',
        ]);
    }
    
    public static function find()
    {
        return new ChangesQuery(get_called_class());
    }
}
