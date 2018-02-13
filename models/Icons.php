<?php
namespace app\models;

use app\traits\TraitBeforeValidate;
use Yii;
use yii\db\ActiveRecord;
use app\models\forms\UploadForm;
use app\helpers\Normalize;

/**
 * This is the model class for table "{{%icons}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $icon_name
 * @property string $photo
 * @property integer $created
 * @property integer $modified
 */
class Icons extends ActiveRecord
{
    use TraitBeforeValidate;
    
    const ICON_NEW = 1;
    const ICON_VIP = 2;
    const ICON_LEADER = 3;
    const ICON_DISCOUNT = 4;
    const ICON_ACTION = 5;
    
    public static function tableName()
    {
        return '{{%icons}}';
    }

    public function rules()
    {
        return [
            [['icon_name', 'photo', 'manager_id', 'created'], 'required'],
            
            [['manager_id', 'created', 'modified'], 'integer'],
            [['icon_name', 'photo'], 'string', 'max' => 50],
        ];
    }
    
    public function beforeValidate()
    {
        $this->traitBeforeValidate();
        return parent::beforeValidate();
    }
    
    public static function getIconPath($icon_id)
    {
        $model = self::findOne($icon_id);
        return $model ? UploadForm::getSrc($model->photo, UploadForm::TYPE_PAGES, '_sm') : '';
    }
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'icon_name' => 'Название',
        ]);
    }
}
