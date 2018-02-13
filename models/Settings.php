<?php

namespace app\models;

use app\helpers\Normalize;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%settings}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $email_username
 * @property string $email_password
 * @property string $email_host
 * @property integer $email_port
 * @property string $email_fromname
 * @property string $email_sign
 * @property string $emails_order
 * @property string $emails_new_camp
 * @property string $emails_edit_camp
 * @property string $emails_change_order_status
 *
 * @property string $social_vk
 * @property string $social_ok
 * @property string $social_fb
 *
 * @property integer $created
 * @property integer camps_main_count
 * @property integer convert_percent
 */
class Settings extends ActiveRecord
{
    protected static $settings;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%settings}}';
    }
    
    public static function getCampsMainCounts() {
        return [3 => 3, 6 => 6, 9 => 9];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manager_id', 'email_username', 'email_password', 'email_host', 'email_port',
              'email_fromname', 'email_sign', 'created'], 'required'],
                                    
            [['email_sign','emails_order','emails_new_camp', 'emails_edit_camp', 'emails_change_order_status'], 'string'],
            [['email_username', 'email_password', 'email_host', 'email_fromname'], 'string', 'max' => 100],
            [['social_vk', 'social_ok', 'social_fb'], 'string', 'max' => 250],

            [['email_username'], 'email'],
            
            [['convert_percent'], 'number', 'min' => 0, 'max' => 100],

            [['camps_main_count'], 'in', 'range' => self::getCampsMainCounts()],
        ];
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) $this->created = time();
        $this->manager_id = Yii::$app->user->id;
    
        $settings = self::lastSettings();
        
        foreach ($this->attributes AS $attr => $val) {
            // установка текущий параметров, если они не были переданы
            // необходимо для сохранения с разных форм
            if ($attr == 'id') continue;
            if (!$this->{$attr}) $this->{$attr} = $settings->{$attr};
        }
        
        return parent::beforeValidate();
    }

    // static model
    public static function lastSettings() {
        if (is_null(self::$settings)) {
            $model = self::find()->orderBy(['id' => SORT_DESC])->one();
            if (!$model) $model = new self();

            self::$settings = $model;
        }
        
        return self::$settings;
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'email_username' => 'Email пользователь',
            'email_password' => 'Email пароль',
            'email_host' => 'Email хост',
            'email_port' => 'Email порт',
            'email_fromname' => 'Отправитель',
            'email_sign' => 'Подпись писем',

            'social_vk' => 'Сообщество ВКонтакте',
            'social_ok' => 'Сообщество Одноклассники',
            'social_fb' => 'Сообщество Facebook',

            'bonuses_rate' => 'Курс, бонусов за рубль',
            'convert_percent' => 'Конвертация валюты',
            
            'emails_order' => 'Уведомления о брони',
            'emails_new_camp' => 'Уведомления о новом лагере',
            'emails_edit_camp' => 'Уведомления об изменении лагеря',
            'emails_change_order_status' => 'Уведомления об изменении статуса заявки',
            
            'camps_main_count' => 'Кол-во лагерей "Забронировать бесплатно"',
        ]);
    }
}
