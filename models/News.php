<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\NewsQuery;
use Yii;

/**
 * This is the model class for table "{{%news}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $photo
 * @property string $title
 * @property string $alias
 * @property string $meta_d
 * @property string $meta_k
 * @property string $meta_t
 * @property string $about
 * @property string $content
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 * @property integer $views
 */
class News extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%news}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['photo', 'title', 'alias', 'about', 'content', 'manager_id', 'created'], 'required'],
            
            ['alias', 'unique'],
            
            [['manager_id', 'created', 'modified', 'status', 'views', 'ordering'], 'integer'],
            [['manager_id', 'created', 'modified', 'status', 'views', 'ordering'], 'default', 'value' => 0],
            
            [['title', 'alias', 'meta_d', 'meta_k', 'meta_t'], 'string', 'max' => 250],
            [['photo'], 'string', 'max' => 50],
            [['about'], 'string', 'max' => 500],
            [['content'], 'string'],

            [['content', 'about', 'photo', 'title', 'alias', 'meta_d', 'meta_k', 'meta_t'], 'default', 'value' => ''],
        ];
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
            'title' => 'Заголовок',
            'meta_t' => 'Meta-заголовок',
            'meta_d' => 'Meta-описание',
            'meta_k' => 'Meta-ключевики',
            'about' => 'Краткое описание',
            'content' => 'Полное описание',
            'views' => 'Кликов',
        ]);
    }

    /**
     * @inheritdoc
     * @return NewsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new NewsQuery(get_called_class());
    }
}
