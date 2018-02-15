<?php
namespace app\models;

use app\helpers\Normalize;
use app\helpers\Statuses;
use app\models\queries\OrdersQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%orders}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $partner_id
 * @property integer $camp_id
 * @property integer $item_id
 * @property integer $user_id
 * @property integer $trans_in_price
 * @property integer $price_partner
 * @property integer $price_user
 * @property integer $price_payed
 * @property string $currency
 * @property string $currency_partner
 * @property string $comment
 * @property string $details
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 *
 * @property Camps $camp
 * @property Users $partner
 * @property BaseItems $campItem
 */
class Orders extends ActiveRecord
{
    const CUR_RUB = 'RUB';
    const CUR_USD = 'USD';
    const CUR_EUR = 'EUR';
    
    const PRICE_STANDARD = 'standard';
    const PRICE_HOT = 'hot';
    
    const SCENARIO_GROUPS = 'groups';
    
    const TYPE_DATA_CHILD = 'child';
    const TYPE_DATA_CLIENT = 'client';
    
    public $child_birth = [];
    public $child_fio = [];
    
    public $children_count;
    public $children_age_from;
    public $children_age_to;
    
    public $client_fio;
    public $client_email;
    public $client_phone;
    public $client_comment;
    
    public static function tableName()
    {
        return '{{%orders}}';
    }
    
    public function rules()
    {
        return [
            [['created', 'camp_id', 'item_id'], 'required', 'message' => 'Выберите смену'],
            
            [['manager_id', 'partner_id', 'camp_id', 'item_id', 'price_partner', 'price_user', 'user_id',
              'price_payed', 'modified', 'status', 'trans_in_price'], 'integer'],
            
            [['manager_id', 'partner_id', 'camp_id', 'item_id', 'price_partner', 'price_user', 'user_id',
              'price_payed', 'modified', 'status', 'trans_in_price'], 'default', 'value' => 0],
            
            [['currency', 'currency_partner'], 'default', 'value' => self::CUR_RUB],
            [['currency', 'currency_partner'], 'in', 'range' => array_keys(self::getCurrencies())],
            
            [['child_birth', 'child_fio'], 'required', 'skipOnEmpty' => false, 'on' => self::SCENARIO_DEFAULT],
            [['child_birth', 'child_fio'], 'each', 'rule' => ['required'], 'on' => self::SCENARIO_DEFAULT],
            [['child_birth', 'child_fio'], 'each', 'rule' => ['filter', 'filter' => 'trim']],
            
            [['children_count', 'children_age_from', 'children_age_to'], 'required', 'on' => self::SCENARIO_GROUPS],
            [['children_age_from', 'children_age_to'], 'integer', 'min' => 1, 'max' => 30],
            [['children_count'], 'integer', 'min' => 1, 'max' => 100],
            
            ['child_birth', 'each', 'rule' => [
                'date',
                'format' => 'dd.MM.yyyy',
                'message' => 'Введите корректную дату рождения, пример: ' . date('d.m.Y', strtotime('now - 10 year'))
            ]],
            
            ['child_fio', 'each', 'rule' => ['string', 'max' => 100]],
            
            [['client_fio', 'client_email', 'client_phone'], 'required'],
            [['client_fio', 'client_email', 'client_phone'], 'filter', 'filter' => 'trim'],
            [['client_fio', 'client_email', 'client_phone'], 'string', 'max' => 100],
            
            ['client_phone', 'string', 'min' => 10, 'tooShort' => 'Введите не менее 10 цифр в поле «{attribute}»'],
            ['client_email', 'email'],
            
            [['comment', 'client_comment'], 'string', 'max' => 500],
            ['details', 'string'],
        ];
    }
    
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_GROUPS] = $scenarios[self::SCENARIO_DEFAULT];
        
        return $scenarios;
    }
        
    public static function getCurrencies()
    {
        return [
            self::CUR_RUB => self::CUR_RUB,
            self::CUR_USD => self::CUR_USD,
            self::CUR_EUR => self::CUR_EUR
        ];
    }
    
    public static function getPriceTypes()
    {
        return [
            self::PRICE_STANDARD => 'Обычная путевка',
            self::PRICE_HOT      => 'Горящая путевка',
        ];
    }
    
    public function getPartner()
    {
        return $this->hasOne(Users::className(), ['id' => 'partner_id']);
    }
    
    public function getCamp()
    {
        return $this->hasOne(Camps::className(), ['id' => 'camp_id']);
    }
    
    public function getCampItem()
    {
        return $this->hasOne(BaseItems::className(), ['id' => 'item_id']);
    }

    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            if (!Yii::$app->user->isGuest) $this->user_id = Yii::$app->user->id;
        }
    
        if (Users::isAdmin()) $this->manager_id = Yii::$app->user->id;
        $this->client_phone = Normalize::clearPhone($this->client_phone);
        
        if ($this->item_id) {
            // устанавливаем параметры лагеря и смены
            $model_item = BaseItems::findOne($this->item_id);
            if ($model_item) {
                $this->partner_id = $model_item->partner_id;
                $this->camp_id = $model_item->camp_id;
                $this->trans_in_price = $model_item->camp->about->trans_in_price;
                
                $this->currency = self::CUR_RUB;
                $this->currency_partner = $model_item->currency;
            }
        }

        return parent::beforeValidate();
    }
    
    /**
     * при заказе нужно уменьшить кол-во путевок
     *
     * @param bool $runValidation
     * @param null $attributeNames
     * @return mixed
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        // учетные статусы путевок для изменения кол-ва
        $count_statuses = [
            Statuses::STATUS_PREPAY,
            Statuses::STATUS_PAYED,
            Statuses::STATUS_PROCESS,
            Statuses::STATUS_CLOSED
        ];
        
        if ($this->isNewRecord) {
            
            if (in_array($this->status, $count_statuses)) {
                // статус учетный сразу - уменьшаем путевки
                $counter = -($this->children_count);
            }
            
        } else {
            
            $old_model = self::findOne($this->id);
            
            if (in_array($this->status, $count_statuses) && !in_array($old_model->status, $count_statuses)) {
                // статус сменился с неучетного на учетный - уменьшаем путевки
                $counter = -($this->children_count);
            } elseif (in_array($old_model->status, $count_statuses) && !in_array($this->status, $count_statuses)) {
                // статус сменился с учетного на неучетный - прибавляем путевки
                $counter = $this->children_count;
            }
        }
        
        $saved = parent::save($runValidation, $attributeNames);
        
        if ($saved && isset($counter)) {
            // инкремент партнерских путевок
            $this->campItem->updateCounters(['partner_amount' => $counter]);
        }
        
        return $saved;
    }
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'user_id' => 'Пользователь',
            'camp_id' => 'Лагерь',
            'item_id' => 'Смена',
            
            'price_partner' => 'Цена от партнера',
            'price_user'    => 'Цена пользователя',
            'price_payed'   => 'Оплачено/Предоплата',
            
            'currency'         => 'Валюта',
            'currency_partner' => 'Валюта партнера',
            
            'details' => 'Детали заказа',
            
            'trans_in_price' => 'Трансфер входит в стоимость',
            
            'client_fio'     => 'Ваше ФИО',
            'client_email'   => 'Ваш Email',
            'client_phone'   => 'Ваш номер телефона',
            'client_comment' => 'Ваш комментарий',
            
            'client_fio_contact'     => 'ФИО клиента',
            'client_email_contact'   => 'Email клиента',
            'client_phone_contact'   => 'Номер телефона',
            'client_comment_contact' => 'Комментарий клиента',
            
            'child_birth' => 'Дата рождения ребенка',
            'child_fio'   => 'ФИО ребенка',
            
            'children_count'    => 'Количество детей',
            'children_age_from' => 'Возраст детей от, лет',
            'children_age_to'   => 'Возраст детей до, лет',
        ]);
    }

    public static function find()
    {
        return new OrdersQuery(get_called_class());
    }
}
