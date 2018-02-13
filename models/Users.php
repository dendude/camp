<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\UsersQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "{{%users}}".
 *
 * @property integer $id
 * @property string $role
 * @property string $first_name
 * @property string $last_name
 * @property string $sur_name
 * @property string $email
 * @property string $phone
 * @property string $pass
 * @property string $photo
 * @property string $act_code
 * @property string $settings
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 * @property integer $last_active
 * @property integer $access_token
 *
 * @property string contacts_boss_fio
 * @property string contacts_boss_phone
 * @property string contacts_boss_email
 * @property string contacts_worker_fio
 * @property string contacts_worker_phone
 * @property string contacts_worker_email
 * @property string contacts_office_address
 * @property string contacts_office_phone
 * @property string contacts_office_route
 * @property string contacts_notify_emails
 * @property string contacts_notify_phones
 *
 * @property string contract_inn
 * @property string contract_ogrn_serial
 * @property string contract_ogrn_number
 * @property string contract_ogrn_date
 *
 * @property Camps[] camps
 */
class Users extends ActiveRecord implements IdentityInterface
{
    public $authKey;
    public $username;
    
    public $pass_origin;
    
    public $contract_ogrn_date_f;
    public $contacts_notify_emails_f;
    
    public $settings_arr = [
        'user_notif_email' => 0,
        'user_notif_phone' => 0,
        
        'partner_notif_camp_email' => 0,
        'partner_notif_camp_phone' => 0,
        'partner_notif_order_email' => 0,
        'partner_notif_order_phone' => 0,
        'partner_notif_finance_email' => 0,
        'partner_notif_finance_phone' => 0,
    ];
    
    static $profile;
    
    const ROLE_ADMIN = 'admin';
    const ROLE_PARTNER = 'partner';
    const ROLE_USER = 'user';
    
    const SCENARIO_OFFICE = 'office';
    
    public static function tableName()
    {
        return '{{%users}}';
    }
    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        $behaviors['authenticator']['class'] = QueryParamAuth::className();
        $behaviors['authenticator']['tokenParam'] = 'token';
        
        return $behaviors;
    }
    
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_OFFICE] = [
            'first_name', 'last_name', 'sur_name', 'phone', 'photo', 'settings_arr',
            'contacts_boss_fio', 'contacts_boss_phone', 'contacts_boss_email',
            'contacts_worker_fio', 'contacts_worker_phone', 'contacts_worker_email',
            'contacts_office_address', 'contacts_office_phone', 'contacts_office_route',
            'contacts_notify_emails', 'contacts_notify_emails_f', 'contacts_notify_phones',
            
            'contract_inn', 'contract_ogrn_serial', 'contract_ogrn_number',
            'contract_ogrn_date', 'contract_ogrn_date_f'
        ];
        
        return $scenarios;
    }
    
    public function rules()
    {
        return [
            [['first_name', 'email', 'created', 'role'], 'required'],

            [['pass', 'pass_origin'], 'required', 'when' => function(self $model){
                return $model->isNewRecord;
            }, 'whenClient' => "function(attribute, value){
                return " . ($this->isNewRecord ? 'true' : 'false') . ";
            }"],
            
            ['role', 'in', 'range' => array_keys(self::getRoles())],
            ['role', 'default', 'value' => self::ROLE_USER],

            ['email', 'email'],
            ['email', 'unique'],
            
            [['created', 'modified', 'status', 'last_active'], 'integer'],
            [['created', 'modified', 'status', 'last_active'], 'default', 'value' => 0],
            
            [['first_name', 'last_name', 'sur_name', 'email', 'pass', 'photo', 'act_code', 'phone'], 'string', 'max' => 100],
            [['first_name', 'last_name', 'sur_name', 'email', 'pass', 'photo', 'act_code', 'phone'], 'default', 'value' => ''],
                        
            ['settings_arr', 'each', 'rule' => ['boolean']],
            ['settings', 'string', 'max' => 1000],

            ['access_token', 'string', 'max' => 100],

            // данные партнера для автовставки
            [['contacts_boss_fio', 'contacts_boss_phone', 'contacts_boss_email',
              'contacts_worker_fio', 'contacts_worker_phone', 'contacts_worker_email',
              'contacts_office_address', 'contacts_office_phone'], 'string', 'max' => 200],

            [['contacts_boss_fio', 'contacts_boss_phone', 'contacts_boss_email',
              'contacts_worker_fio', 'contacts_worker_phone', 'contacts_worker_email',
              'contacts_office_address', 'contacts_office_phone'], 'default', 'value' => ''],

            ['contacts_office_route', 'string', 'max' => 500],
            ['contacts_office_route', 'default', 'value' => ''],

            [['contract_inn', 'contract_ogrn_serial', 'contract_ogrn_number'], 'string', 'max' => 200],
            [['contract_inn', 'contract_ogrn_serial', 'contract_ogrn_number'], 'default', 'value' => ''],

            [['contacts_notify_emails', 'contacts_notify_phones'], 'string', 'max' => 1000],
            [['contacts_notify_emails_f'], 'each', 'rule' => ['email', 'message' => '{value} не является корректным Email-адресом']],
            
            [['contract_ogrn_date'], 'date', 'format' => 'yyyy-MM-dd'],
            [['contract_ogrn_date_f'], 'date', 'format' => 'dd.MM.yyyy'],
        ];
    }
    
    public function getCamps()
    {
        return $this->hasMany(Camps::className(), ['partner_id' => 'id']);
    }
    
    public static function getRoles() {
        return [
            self::ROLE_USER => 'Пользователь',
            self::ROLE_PARTNER => 'Партнер',
            self::ROLE_ADMIN => 'Администратор',
        ];
    }
    
    public function getRoleName() {
        $list = self::getRoles();
        return isset($list[$this->role]) ? $list[$this->role] : 'role not found';
    }

    public function getFullName() {
        return trim($this->last_name . ' ' . $this->first_name . ' ' . $this->sur_name);
    }
    
    public function setNewActCode() {
        $this->act_code = preg_replace('/[\W\-\_]/', '', Yii::$app->security->generateRandomString());
        if (!$this->isNewRecord) $this->update();
    }
    
    public static function isAdmin() {
        if (Yii::$app->user->isGuest) return false;
        return (Yii::$app->user->identity->role == self::ROLE_ADMIN);
    }

    public static function isPartner() {
        if (Yii::$app->user->isGuest) return false;
        return (Yii::$app->user->identity->role == self::ROLE_PARTNER || self::isAdmin());
    }
    
    public function getManagerName() {
        return $this->first_name . ' ' . $this->last_name . ' [' . $this->getRoleName() . ']';
    }
    
    public static function getStaticManagerName($manager_id) {
        $user = self::findOne($manager_id);
        return $user ? $user->getManagerName() : '';
    }
    
    public static function getPartnersFilter() {
        $list = self::find()->where(['role' => self::ROLE_PARTNER])->all();
        return $list ? ArrayHelper::map($list, 'id', function(self $model){
            return $model->getFullName();
        }) : [];
    }
    
    public static function getManagersFilter() {
        $list = self::find()->where(['role' => self::ROLE_ADMIN])->all();
        return $list ? ArrayHelper::map($list, 'id', function(self $model){
            return $model->getManagerName();
        }) : [];
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        $settings = $this->settings ? json_decode($this->settings, true) : [];
        $this->settings = Json::encode(array_merge($settings, $this->settings_arr));
        $this->contract_ogrn_date = Normalize::getSqlDate($this->contract_ogrn_date_f);
        
        $this->phone = Normalize::clearPhone($this->phone);
        $this->contacts_boss_phone = Normalize::clearPhone($this->contacts_boss_phone);
        $this->contacts_worker_phone = Normalize::clearPhone($this->contacts_worker_phone);
        $this->contacts_office_phone = Normalize::clearPhone($this->contacts_office_phone);
        
        if ($this->pass_origin) {
            $this->pass = Yii::$app->security->generatePasswordHash($this->pass_origin);
        }
        
        if ($this->contacts_notify_emails) { // для валидации каждого емайла
            $this->contacts_notify_emails_f = explode(',', str_replace(' ', '', $this->contacts_notify_emails));
        }
        
        return parent::beforeValidate();
    }
    
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $validate = parent::validate($attributeNames, $clearErrors);
        if ($this->hasErrors('contacts_notify_emails_f')) {
            $this->addError('contacts_notify_emails', $this->getFirstError('contacts_notify_emails_f'));
        }
        
        return $validate;
    }
    
    public function afterFind()
    {
        $this->settings_arr = Json::decode($this->settings);
        $this->contract_ogrn_date_f = Normalize::getDate($this->contract_ogrn_date);
        
        $this->phone = Normalize::formatPhone($this->phone);
        $this->contacts_boss_phone = Normalize::formatPhone($this->contacts_boss_phone);
        $this->contacts_worker_phone = Normalize::formatPhone($this->contacts_worker_phone);
        $this->contacts_office_phone = Normalize::formatPhone($this->contacts_office_phone);
        
        parent::afterFind();
    }
    
    public function save($runValidation = true, $attributeNames = null)
    {
        $is_new = $this->isNewRecord;
        $saved = parent::save($runValidation, $attributeNames);
    
        if (!$this->camps) return $saved;
        
        if ($saved && !$is_new) {
            $camps_ids = ArrayHelper::map($this->camps, 'id', 'id');
            
            // обновляем данные лагерей партнера
            CampsContacts::updateAll([
                'boss_fio' => $this->contacts_boss_fio,
                'boss_phone' =>$this->contacts_boss_phone,
                'boss_email' => $this->contacts_boss_email,
                'worker_fio' => $this->contacts_worker_fio,
                'worker_phone' => $this->contacts_worker_phone,
                'worker_email' => $this->contacts_worker_email,
                'office_address' => $this->contacts_office_address,
                'office_phone' => $this->contacts_office_phone,
                'office_route' => $this->contacts_office_route,
                'notif_order_emails' => $this->contacts_notify_emails,
                'notif_order_phone' => $this->contacts_notify_phones,
            ], ['camp_id' => $camps_ids]);
    
            CampsContract::updateAll([
                'contract_ogrn_serial' => $this->contract_ogrn_serial,
                'contract_ogrn_number' =>$this->contract_ogrn_number,
                'contract_ogrn_date' => $this->contract_ogrn_date,
                'contract_inn' => $this->contract_inn,
            ], ['camp_id' => $camps_ids]);
        }
        
        return $saved;
    }
    
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'role' => 'Роль',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'sur_name' => 'Отчество',
            'phone' => 'Телефон',
            'email' => 'Email',
            'pass' => 'Пароль',
            'pass_origin' => 'Пароль',
            'last_active' => 'Активность',

            'contacts_boss_fio' => 'ФИО директора',
            'contacts_boss_phone' => 'Телефон директора',
            'contacts_boss_email' => 'Email директора',
            'contacts_worker_fio' => 'ФИО сотрудника',
            'contacts_worker_phone' => 'Телефон сотрудника',
            'contacts_worker_email' => 'Email сотрудника',
            'contacts_office_address' => 'Адрес офиса',
            'contacts_office_phone' => 'Телефон офиса',
            'contacts_office_route' => 'Маршрут и график',
            
            'contacts_notify_emails' => 'Email для уведомлений',
            'contacts_notify_emails_f' => 'Email для уведомлений',
            'contacts_notify_phones' => 'Телефоны для уведомлений',

            'contract_inn' => 'ИНН',
            'contract_ogrn_number' => 'ОГРН номер',
            'contract_ogrn_serial' => 'ОГРН серия',
            'contract_ogrn_date' => 'ОГРН дата',
            'contract_ogrn_date_f' => 'ОГРН дата',
        ]);
    }
    
    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->pass);
    }
    
    public static function find(){
        return new UsersQuery(get_called_class());
    }
    
    public static function getProfile() {
        return self::findIdentity(Yii::$app->user->id);
    }
    
    public static function findIdentity($id)
    {
        if (!self::$profile) self::$profile = static::findOne($id);
        Users::updateAll(['last_active' => time()], ['id' => $id]);

        return self::$profile;
    }
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getAuthKey()
    {
        return $this->authKey;
    }
    
    public function validateAuthKey($authKey)
    {
        return ($this->authKey === $authKey);
    }
}
