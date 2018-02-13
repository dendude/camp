<?php
namespace app\models;

use app\models\queries\SelectionsQuery;
use Yii;
use yii\db\ActiveRecord;
use app\helpers\Normalize;

/**
 * This is the model class for table "{{%selections}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $type_id
 * @property integer $created
 * @property integer $modified
 * @property integer $ordering
 * @property integer $status
 * @property string $photo
 *
 * @property TagsTypes $type
 */
class Selections extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%selections}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manager_id', 'type_id', 'photo', 'created'], 'required'],
            
            [['manager_id', 'type_id', 'created', 'modified', 'ordering', 'status'], 'integer'],
            [['manager_id', 'type_id', 'created', 'modified', 'ordering', 'status'], 'default', 'value' => 0],

            ['photo', 'string', 'max' => 50],

            [['type_id'], 'checkUnique'],
        ];
    }
    
    public function checkUnique($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (self::find()->where([$attribute => $this->{$attribute}])
                ->andWhere('id != :id', [':id' => $this->id])->usage()->exists()) {
                $this->addError($attribute, 'Такой ' . $this->getAttributeLabel($attribute) . ' уже выбран в подборке');
            }
        }
    }
    
    public function getType()
    {
        return $this->hasOne(TagsTypes::className(), ['id' => 'type_id']);
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        $this->manager_id = Yii::$app->user->id;
        
        return parent::beforeValidate();
    }
    
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'type_id' => 'Тип лагеря',
        ]);
    }

    public static function find()
    {
        return new SelectionsQuery(get_called_class());
    }
}
