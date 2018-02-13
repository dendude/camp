<?php
namespace app\models;

use app\helpers\Normalize;
use app\models\queries\ComfortTypesQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%comfort_types}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $icon
 * @property string $title
 * @property string $alias
 * @property string $content
 * @property integer $created
 * @property integer $modified
 * @property integer $ordering
 * @property integer $status
 */
class ComfortTypes extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%comfort_types}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manager_id', 'created', 'icon', 'title', 'alias'], 'required'],
            
            [['manager_id', 'created', 'modified', 'ordering', 'status'], 'integer'],
            [['manager_id', 'created', 'modified', 'ordering', 'status'], 'default', 'value' => 0],
            
            [['icon', 'title', 'alias'], 'string', 'max' => 100],
            [['content'], 'string'],
        ];
    }
    
    public static function getFilterList($is_full = false) {
        $query = self::find()->ordering();
        if ($is_full) {
            $query->using();
        } else {
            $query->active();
        }
        $list = $query->all();
        
        return $list ? ArrayHelper::map($list, 'id', 'title') : [];
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        $this->manager_id = \Yii::$app->user->id;
        
        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'icon' => 'Иконка',
            'title' => 'Название',
            'content' => 'Описание услуги',
        ]);
    }

    public static function find()
    {
        return new ComfortTypesQuery(get_called_class());
    }
}
