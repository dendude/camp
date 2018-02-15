<?php
namespace app\models;

use app\helpers\Normalize;
use app\helpers\Statuses;
use app\models\queries\CampsQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%camps}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $partner_id
 * @property string $alias
 * @property string $stars
 * @property string $meta_t
 * @property string $meta_d
 * @property string $meta_k
 * @property integer $incamp_id
 * @property string $incamp_url
 * @property integer $is_new
 * @property integer $is_leader
 * @property integer $is_rating
 * @property integer $is_recommend
 * @property integer $is_vip
 * @property integer $is_main
 * @property integer $created
 * @property integer $modified
 * @property integer $ordering
 * @property integer $min_price
 * @property integer $status
 *
 * @property BaseItems[] $items
 * @property BaseItems[] $itemsActive
 */
class Camps extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%camps}}';
    }

    public function rules()
    {
        return [
            [['manager_id', 'partner_id', 'incamp_id', 'is_new', 'is_leader', 'is_rating', 'is_recommend',
              'is_vip', 'is_main', 'created', 'modified', 'ordering', 'min_price', 'status'], 'integer'],

            [['manager_id', 'partner_id', 'incamp_id', 'is_new', 'is_leader', 'is_rating', 'is_recommend',
              'is_vip', 'is_main', 'created', 'modified', 'ordering', 'min_price', 'status'], 'default', 'value' => 0],
            
            [['stars'], 'number'],
            [['stars'], 'default', 'value' => 0],
            
            [['alias'], 'string', 'max' => 200],
            [['meta_t', 'meta_d', 'meta_k'], 'string', 'max' => 255],
            [['incamp_url'], 'string', 'max' => 500],
        ];
    }
    
    /**
     * смены по текущему лагерю
     *
     * @return mixed
     */
    public function getItems()
    {
        return $this->hasMany(BaseItems::className(), ['camp_id' => 'id'])->ordering();
    }
    
    /**
     * доступные смены лагеря
     *
     * @return mixed
     */
    public function getItemsActive()
    {
        return $this->hasMany(BaseItems::className(), ['camp_id' => 'id'])->active()->ordering();
    }
    
    /**
     * получение полной ссылки на лагерь
     *
     * @param bool $scheme
     * @return mixed
     */
    public function getCampUrl($scheme = false)
    {
        return Url::to(["/camp/{$this->alias}-{$this->id}"], $scheme);
    }
    
    public static function getFilterList($full = false, $partner_id = null)
    {
        $query = self::find()->ordering();
        
        if ($full !== true) $query->active();
        if (is_numeric($partner_id)) $query->byPartner($partner_id);
        
        $list = $query->all();
        
        return $list ? ArrayHelper::map($list, 'id', function (self $model) {
            return $model->getFullName();
        }) : [];
    }
    
    /**
     * вместо реального удаления ставим отметку об удалении
     */
    public function setDeleteStatus()
    {
        $this->updateAttributes(['status' => Statuses::STATUS_REMOVED]);
        BaseItems::updateAll(['status' => Statuses::STATUS_REMOVED], ['camp_id' => $this->id]);
    }
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'partner_id' => 'Партнер',
            'stars'      => 'Stars',
            
            'incamp_id'  => 'Incamp ID',
            'incamp_url' => 'Incamp Url',
            
            'is_main'      => 'На главной в Забронировать бесплатно',
            'is_recommend' => 'Отображать в подборках',
            'is_rating'    => 'Рейтинговое',
            
            'is_vip'    => 'VIP',
            'is_leader' => 'Лидер продаж',
            'is_new'    => 'Новый',
        ]);
    }
    
    public static function find()
    {
        return new CampsQuery(get_called_class());
    }
}
