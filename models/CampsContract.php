<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\helpers\Normalize;
use app\models\queries\CampsContractQuery;
use yii\helpers\Html;

/**
 * This is the model class for table "{{%camps_contract}}".
 *
 * @property integer $id
 * @property integer $camp_id
 * @property integer $contract_number
 * @property string $contract_date
 * @property integer $contract_comission
 * @property string $contract_comission_type
 * @property string $contract_ogrn_number
 * @property string $contract_ogrn_serial
 * @property string $contract_ogrn_date
 * @property string $contract_inn
 * @property string $contract_period_type
 * @property integer $opt_gos_compensation
 * @property integer $opt_group_use
 * @property integer $opt_group_discount
 * @property integer $opt_group_count
 * @property integer $opt_group_guides
 * @property integer $opt_use_paytravel
 */
class CampsContract extends ActiveRecord
{
    public $contract_date_f;
    public $contract_ogrn_date_f;
    
    const COMISSION_SUM = 'sum';
    const COMISSION_PERCENT = 'percent';
    
    const PERIOD_ALWAYS = 'always';
    const PERIOD_ITEMS = 'items';
    
    const SCENARIO_ADMIN = 'admin';
    const SCENARIO_PARTNER = 'partner';
    
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADMIN] = $scenarios[self::SCENARIO_DEFAULT];
        $scenarios[self::SCENARIO_PARTNER] = ['contract_ogrn_serial', 'contract_ogrn_number', 'contract_ogrn_date', 'contract_inn', 'contract_period_type'];
        
        return $scenarios;
    }
    
    public static function tableName()
    {
        return '{{%camps_contract}}';
    }

    public function rules()
    {
        return [
            [['contract_ogrn_number', 'contract_inn'], 'required', 'on' => self::SCENARIO_PARTNER],
        
            [['camp_id', 'contract_number', 'contract_comission', 'contract_ogrn_number', 'contract_inn', 'contract_comission'], 'integer'],
            
            [['contract_comission'], 'string', 'max' => 2, 'when' => function(self $model){
                return ($model->contract_comission_type == self::COMISSION_PERCENT);
            }, 'enableClientValidation' => false],
            [['contract_comission'], 'integer', 'min' => 1, 'max' => 99, 'when' => function(self $model){
                return ($model->contract_comission_type == self::COMISSION_PERCENT);
            }, 'enableClientValidation' => false],
        
            [['contract_inn'], 'string', 'length' => 10],
            [['contract_ogrn_number'], 'string', 'min' => 13, 'max' => 15],
        
            [['contract_comission_type'], 'in', 'range' => array_keys(self::getComissionTypes())],

            [['contract_date', 'contract_ogrn_date'], 'date', 'format' => 'yyyy-MM-dd'],
            [['contract_date_f', 'contract_ogrn_date_f'], 'date', 'format' => 'dd.MM.yyyy'],
            
            [['contract_ogrn_serial'], 'string', 'max' => 100],
        
            ['contract_period_type', 'in', 'range' => array_keys(CampsContract::getPeriodTypes())],
            
            ['opt_use_paytravel', 'boolean'],
            ['opt_use_paytravel', 'default', 'value' => 0],
            
            [['opt_group_discount', 'opt_group_count', 'opt_group_guides'], 'required', 'when' => function(self $model){
                return $model->opt_group_use;
            }, 'whenClient' => "function(attribute, value){
                return $('#" . Html::getInputId($this, 'opt_group_use') . "').prop('checked');
            }"],

            [['opt_gos_compensation', 'opt_group_use'], 'integer'],
            [['opt_gos_compensation', 'opt_group_use'], 'default', 'value' => 0],
            
            [['opt_group_discount'], 'integer', 'min' => 1, 'max' => 100],
            [['opt_group_count'], 'integer', 'min' => 2, 'max' => 100],
            [['opt_group_guides'], 'integer', 'min' => 1],
        ];
    }
    
    public static function getComissionTypes() {
        return [
            self::COMISSION_SUM => 'Фиксированная сумма',
            self::COMISSION_PERCENT => 'Процент от суммы'
        ];
    }
    public function getComissionName($type = null) {
        $list = self::getComissionTypes();
        
        if ($type) {
            return isset($list[$type]) ? $list[$type] : 'not found';
        }
        
        return isset($list[$this->contract_comission_type]) ? $list[$this->contract_comission_type] : 'not found';
    }
    public function getComissionSymbol() {
        return $this->contract_comission_type == self::COMISSION_SUM ? Orders::CUR_RUB : '%';
    }
    
    public static function getPeriodTypes() {
        return [
            self::PERIOD_ALWAYS => 'Круглый год',
            self::PERIOD_ITEMS => 'Периодично',
        ];
    }
    public function getPeriodName() {
        $list = self::getPeriodTypes();
        return isset($list[$this->contract_period_type]) ? $list[$this->contract_period_type] : 'not found';
    }
    
    public function beforeValidate()
    {
        $this->contract_date = Normalize::getSqlDate($this->contract_date_f);
        $this->contract_ogrn_date = Normalize::getSqlDate($this->contract_ogrn_date_f);
            
        return parent::beforeValidate();
    }
    
    public function afterFind()
    {
        if ($this->contract_date == date('Y-m-d', 0)) {
            $this->contract_date_f = '';
        } else {
            $this->contract_date_f = Normalize::getDate($this->contract_date);
        }
        
        if ($this->contract_ogrn_date == date('Y-m-d', 0)) {
            $this->contract_ogrn_date_f = '';
        } else {
            $this->contract_ogrn_date_f = Normalize::getDate($this->contract_ogrn_date);
        }
        
        parent::afterFind();
    }
    
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'contract_number' => '№ договора',
            
            'contract_date' => 'Дата договора',
            'contract_date_f' => 'Дата договора',
            
            'contract_comission' => 'Комиссия',
            'contract_comission_type' => 'Тип комиссии',
            
            'contract_ogrn_number' => 'ОГРН номер',
            'contract_ogrn_serial' => 'ОГРН серия',
            
            'contract_ogrn_date' => 'ОГРН дата',
            'contract_ogrn_date_f' => 'ОГРН дата',
            
            'contract_inn' => 'ИНН',
            
            'contract_period_type' => 'Сезонность',

            'opt_use_paytravel' => 'Оплата через PayTravel подключена',
            'opt_gos_compensation' => 'Наличие государственной компенсации',
            'opt_group_use' => 'Групповая скидка',
            'opt_group_discount' => 'Размер групповой скидки, %',
            'opt_group_count' => 'Минимальное кол-во участников в группе для скидки',
            'opt_group_guides' => 'Кол-во сопровождающих',
        ]);
    }

    public static function find()
    {
        return new CampsContractQuery(get_called_class());
    }
}
