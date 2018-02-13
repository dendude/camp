<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\ReviewsItemsQuery;
use Yii;

/**
 * This is the model class for table "{{%reviews_items}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $title
 * @property string $about
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 * @property integer $ordering
 */
class ReviewsItems extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%reviews_items}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'manager_id', 'created'], 'required'],
            
            [['manager_id', 'created', 'modified', 'status', 'ordering'], 'integer'],
            [['manager_id', 'created', 'modified', 'status', 'ordering'], 'default', 'value' => 0],
            
            ['title', 'unique'],
            
            [['title'], 'string', 'max' => 100],
            [['about'], 'string', 'max' => 1000],
        ];
    }
    
    public static function getVoteItems() {
        return [
            1 => 'Очень плохо',
            2 => 'Плохо',
            3 => 'Нормально',
            4 => 'Хорошо',
            5 => 'Замечательно',
        ];
    }
    public static function getVoteName($id) {
        $list = self::getVoteItems();
        return isset($list[$id]) ? $list[$id] : 'not found';
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
    
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'title' => 'Название',
            'about' => 'Описание',
        ]);
    }

    public static function find()
    {
        return new ReviewsItemsQuery(get_called_class());
    }
}
