<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\helpers\Normalize;
use app\models\queries\CampsClientQuery;

/**
 * This is the model class for table "{{%camps_client}}".
 *
 * @property integer $id
 * @property integer $camp_id
 * @property string $info_payment
 * @property string $info_common
 * @property string $info_dops
 * @property string $info_docs
 * @property string $info_bags
 * @property string $info_visa
 */
class CampsClient extends ActiveRecord
{
    const VISA_NONE = 'none';
    const VISA_STANDARD = 'standard';
    const VISA_SCHENGEN = 'schengen';
    
    public static function tableName()
    {
        return '{{%camps_client}}';
    }

    public function rules()
    {
        return [
            [['info_payment', 'info_docs', 'info_visa'], 'required'],
            
            [['camp_id'], 'integer'],
            [['info_payment', 'info_common', 'info_dops', 'info_docs', 'info_bags', 'info_visa'], 'string'],

            [['info_visa'], 'in', 'range' => array_keys(self::getVisaTypes())],
        ];
    }
    
    public static function getVisaTypes()
    {
        return [
            self::VISA_NONE => 'Не требуется',
            self::VISA_STANDARD => 'Требуется стандартная',
            self::VISA_SCHENGEN => 'Требуется шенгенская',
        ];
    }
    public function getVisaName()
    {
        $list = self::getVisaTypes();
        return isset($list[$this->info_visa]) ? $list[$this->info_visa] : 'not found';
    }

    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'info_payment' => 'В стоимость включено',
            'info_common' => 'Общая информация',
            'info_dops' => 'Дополнительно оплачиваются',
            'info_docs' => 'Обязательные документы',
            'info_bags' => 'Что положить в чемодан',
            'info_visa' => 'Требуется ли виза',
        ]);
    }

    public static function find()
    {
        return new CampsClientQuery(get_called_class());
    }
}
