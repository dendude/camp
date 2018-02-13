<?php

namespace app\models;

use app\components\BankCourse;
use app\helpers\Normalize;
use app\helpers\Statuses;
use app\models\queries\BaseItemsQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * This is the model class for table "{{%base_items}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $partner_id
 * @property integer $camp_id
 * @property string $name_short
 * @property string $name_full
 * @property string $date_from
 * @property string $date_to
 * @property string $currency
 *
 * @property integer $partner_amount
 * @property integer $partner_price
 *
 * @property string $comission_type
 * @property integer $comission_value
 *
 * @property string $discount_type
 * @property integer $discount_value
 *
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 *
 * @property Camps $camp
 */
class BaseItems extends ActiveRecord
{
    public $date_from_orig;
    public $date_to_orig;
    
    // проверка максимальной комиссии
    public $compare_comission = 0;
    
    // проверка максимальной скидки
    public $compare_discount = 0;
        
    const SCENARIO_PARTNER = 'partner';
    const SCENARIO_ADMIN = 'admin';
    
    const TYPE_PARTNER = 'partner';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%base_items}}';
    }
    
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        
        $scenarios[self::SCENARIO_PARTNER] = [
            'name_short', 'name_full', 'partner_amount', 'partner_price', 'currency',
            'date_from_orig', 'date_to_orig', 'date_from', 'date_to'
        ];
        $scenarios[self::SCENARIO_ADMIN] = array_merge($scenarios[self::SCENARIO_PARTNER], [
            'comission_type', 'comission_value', 'discount_type', 'discount_value', 'status'
        ]);
        
        return $scenarios;
    }
    
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['partner_amount', 'partner_price', 'currency',
              'date_from', 'date_to', 'date_from_orig', 'date_to_orig',
              'name_short', 'name_full', 'created'], 'required'],
            
            [['manager_id', 'partner_id', 'camp_id', 'partner_amount', 'partner_price', 'created', 'modified', 'status'], 'integer'],
            [['manager_id', 'partner_id', 'camp_id', 'partner_amount', 'partner_price', 'created', 'modified', 'status'], 'default', 'value' => 0],
            
            [['date_from', 'date_to'], 'date', 'format' => 'yyyy-MM-dd'],
            [['date_from_orig', 'date_to_orig'], 'date', 'format' => 'dd.MM.yyyy'],
                        
            ['date_to', 'compare', 'compareAttribute' => 'date_from', 'operator' => '>=', 'when' => function(self $model){
                // сравниваем даты смены
                return ($model->date_from_orig && $model->date_to_orig);
            }],
            
            [['date_from', 'date_to'], 'compare', 'compareValue' => date('Y-m-d'), 'operator' => '>=', 'when' => function(self $model){
                return $model->isNewRecord;
            }, 'message' => 'Выберите дату не ранее ' . date('d.m.Y')],
            
            ['currency', 'in', 'range' => array_keys(Orders::getCurrencies())],
            ['currency', 'default', 'value' => Orders::CUR_RUB],
            
            ['name_short', 'string', 'max' => 50],
            ['name_full', 'string', 'max' => 100],

            // преобразование к числу
            [['partner_price', 'partner_amount'], 'filter', 'filter' => 'intval'],
            [['comission_value', 'discount_value'], 'filter', 'filter' => 'intval'],

            // проверка величины комиссии
            ['comission_value', 'integer', 'min' => 0],
            ['comission_value', 'default', 'value' => 0],
            ['comission_value', 'checkComission'],

            // проверка величины скидки от комиссии
            ['discount_value', 'integer', 'min' => 0],
            ['discount_value', 'default', 'value' => 0],
            ['discount_value', 'checkDiscount'],
        ];
    }
    
    /**
     * проверка комиссии
     * @param $attribute
     * @param $params
     */
    public function checkComission($attribute, $params)
    {
        if ($this->hasErrors()) return;
        
        if ($this->comission_value > $this->compare_comission) {
            if ($this->comission_type == CampsContract::COMISSION_PERCENT) {
                $this->addError($attribute, 'Введите не более 100%');
            } else {
                $this->addError($attribute, "Введите не более {$this->compare_comission} {$this->currency}");
            }
        }
    }
    
    /**
     * проверка скидки
     * @param $attribute
     * @param $params
     */
    public function checkDiscount($attribute, $params)
    {
        if ($this->hasErrors()) return;
    
        if ($this->discount_value > $this->compare_discount) {
            if ($this->discount_type == CampsContract::COMISSION_PERCENT) {
                $this->addError($attribute, 'Введите не более 100%');
            } else {
                $this->addError($attribute, "Введите не более {$this->compare_discount} {$this->currency}");
            }
        }
    }
    
    public function getCamp() {
        return $this->hasOne(Camps::className(), ['id' => 'camp_id']);
    }
    
    public static function getFilterListOrder($camp_id, $full = false) {
        $query = self::find()->byCamp($camp_id)->ordering();
        if ($full === false) $query->active();
        $list = $query->all();
        
        return $list ? ArrayHelper::map($list, 'id', function(self $model){
            // выводим запись: [01.01 - 09.01] 1 смена [10 мест]
            return '[' . date('d.m', strtotime($model->date_from)) . ' - ' . date('d.m', strtotime($model->date_to)) . '] '
                       . $model->name_short . ' [' . Normalize::wordAmount($model->partner_amount, ['мест','место','места'], true) . ']';
        }) : [];
    }
    
    public function getCurrentPrice($use_currency = false, $use_group = false, $type_price = null) {
        $bank = new BankCourse();
        
        $result = $this->partner_price;
    
        if ($this->discount_value) {
            // цена с учетом скидки
            
            if ($this->discount_type == CampsContract::COMISSION_PERCENT) {
                if ($this->comission_type == CampsContract::COMISSION_PERCENT) {
                    // сумма комиссии от процента
                    $comission_sum = round($this->partner_price * $this->comission_value / 100);
                } else {
                    // сумма комиссии как значение
                    $comission_sum = $this->comission_value;
                }
        
                // скидка как процент от суммы комиссии
                $result -= round($comission_sum * $this->discount_value / 100);
            } else {
                // скидка как значение
                $result -= $this->discount_value;
            }
        }
        
        $result = $bank->convertToRubs($this->currency, $result);
        
        if ($this->camp->contract->opt_group_use && $use_group) {
            // есть групповая скидка
            $result *= (1 - $this->camp->contract->opt_group_discount / 100);
        }
    
        $result = (ceil($result / 100) * 100); // округляем вверх до 100 рублей
        if ($use_currency) $result.= (' ' . Html::tag('i', 'p', ['class' => 'als-rub']));
        
        return $result;
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
            
            if ($this->scenario == self::SCENARIO_PARTNER) $this->status = Statuses::STATUS_ACTIVE;
            if (Users::isPartner()) $this->partner_id = Yii::$app->user->id;
        } else {
            $this->modified = time();
        }
        
        if (Users::isAdmin()) $this->manager_id = Yii::$app->user->id;
        if ($this->date_from_orig) $this->date_from = Normalize::getSqlDate($this->date_from_orig);
        if ($this->date_to_orig) $this->date_to = Normalize::getSqlDate($this->date_to_orig);

        if (empty($this->currency)) $this->currency = Orders::CUR_RUB;
        
        return parent::beforeValidate();
    }

    public function getCurrentCurrency()
    {
        if ($this->isNewRecord) return Orders::CUR_RUB;
        return $this->currency;
    }
    
    public static function getDaysPerItem()
    {
        $arr = [];
        for ($i = 1; $i <= 30; $i++) {
            $arr[$i] = Normalize::wordAmount($i, ['дней','день','дня'], true);
        }
        
        return $arr;
    }
    
    public function afterFind()
    {
        $this->date_from_orig = Normalize::getDate($this->date_from);
        $this->date_to_orig = Normalize::getDate($this->date_to);
        
        parent::afterFind();
    }
    
    public function getAttributes($names = null, $except = [])
    {
        $attributes = parent::getAttributes($names, $except);
        $attributes['date_from_orig'] = $this->date_from_orig;
        $attributes['date_to_orig'] = $this->date_to_orig;
        
        return $attributes;
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        if ($this->scenario == self::SCENARIO_PARTNER) {
            return Normalize::withCommonLabels([
                'name_short' => 'Короткое название',
                'name_full' => 'Полное название',
        
                'date_from' => 'Дата с',
                'date_from_orig' => 'Дата с',
                'date_to' => 'Дата по',
                'date_to_orig' => 'Дата по',
        
                'partner_amount' => 'Кол-во путевок',
                'partner_price' => 'Цена путевки',
            ]);
        }
        
        return Normalize::withCommonLabels([
            'partner_id' => 'Партнер',
            'camp_id' => 'Лагерь',
            
            'name_short' => 'Короткое название',
            'name_full' => 'Полное название',
            
            'date_from' => 'Дата с',
            'date_from_orig' => 'Дата с',
            'date_to' => 'Дата по',
            'date_to_orig' => 'Дата по',

            'currency' => 'Валюта',
            'partner_amount' => 'Кол-во путевок',
            'partner_price' => 'Цена партнера',
            
            'comission_type' => 'Тип комиссии',
            'comission_value' => 'Значение комиссии',
            'discount_type' => 'Тип скидки',
            'discount_value' => 'Значение скидки',
        ]);
    }

    public static function find()
    {
        return new BaseItemsQuery(get_called_class());
    }
}
