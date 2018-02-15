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
 * Таблица со сменами для детских лагерей
 *
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

    const COMISSION_PERCENT = 'percent';
    const COMISSION_VALUE = 'value';
    
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
     * получение лагеря смены
     *
     * @return mixed
     */
    public function getCamp()
    {
        return $this->hasOne(Camps::className(), ['id' => 'camp_id']);
    }
    
    /**
     * проверка комиссии
     *
     * @param $attribute
     * @param $params
     */
    public function checkComission($attribute, $params)
    {
        if ($this->hasErrors()) return;
        
        if ($this->comission_value > $this->compare_comission) {
            if ($this->comission_type == self::COMISSION_PERCENT) {
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
            if ($this->discount_type == self::COMISSION_PERCENT) {
                $this->addError($attribute, 'Введите не более 100%');
            } else {
                $this->addError($attribute, "Введите не более {$this->compare_discount} {$this->currency}");
            }
        }
    }
    
    /**
     * преобразование данных перед валидацией
     *
     * @return mixed
     */
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            if (Users::isPartner()) $this->partner_id = Yii::$app->user->id;
        }
        
        if (Users::isAdmin()) $this->manager_id = Yii::$app->user->id;
        
        if ($this->date_from_orig) $this->date_from = Normalize::getSqlDate($this->date_from_orig);
        if ($this->date_to_orig) $this->date_to = Normalize::getSqlDate($this->date_to_orig);

        if (empty($this->currency)) $this->currency = Orders::CUR_RUB;
        
        return parent::beforeValidate();
    }
    
    /**
     * используем слияние массивов для часто-используемых лейблов
     *
     * @return array
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
