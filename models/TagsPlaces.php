<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\TagsPlacesQuery;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%tags_places}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $icon
 * @property string $title
 * @property string $alias
 * @property string $meta_t
 * @property string $meta_d
 * @property string $meta_k
 * @property integer $ordering
 * @property integer $status
 * @property integer $created
 * @property integer $modified
 */
class TagsPlaces extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tags_places}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manager_id', 'created', 'title', 'alias'], 'required'],
            
            ['alias', 'unique'],
            
            [['manager_id', 'ordering', 'status', 'created', 'modified'], 'integer'],
            [['manager_id', 'ordering', 'status', 'created', 'modified'], 'default', 'value' => 0],
            
            [['icon', 'title', 'alias', 'meta_t', 'meta_d', 'meta_k'], 'string', 'max' => 250],
            [['icon', 'title', 'alias', 'meta_t', 'meta_d', 'meta_k'], 'default', 'value' => ''],
        ];
    }
    
    public static function getFilterList() {
        $list = self::find()->ordering()->active()->all();
        return $list ? ArrayHelper::map($list, 'id', 'title') : [];
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        $this->manager_id = Yii::$app->user->id;
        if (empty($this->alias)) $this->alias = Normalize::alias($this->title);
        
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
        ]);
    }

    /**
     * @inheritdoc
     * @return TagsPlacesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TagsPlacesQuery(get_called_class());
    }
}
