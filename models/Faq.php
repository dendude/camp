<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\FaqQuery;
use Yii;

/**
 * This is the model class for table "{{%faq}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $title
 * @property string $question
 * @property string $answer
 * @property string $meta_t
 * @property string $meta_d
 * @property string $meta_k
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 * @property integer $ordering
 */
class Faq extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%faq}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'question', 'answer', 'manager_id', 'created'], 'required'],
            
            [['manager_id', 'created', 'modified', 'status', 'ordering'], 'integer'],
            [['manager_id', 'created', 'modified', 'status', 'ordering'], 'default', 'value' => 0],
            
            [['title'], 'string', 'max' => 100],
            [['question', 'meta_t', 'meta_d', 'meta_k'], 'string', 'max' => 250],
            [['answer'], 'string'],
        ];
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        if (Users::isAdmin()) $this->manager_id = Yii::$app->user->id;
        
        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'title' => 'Заголовок',
            'question' => 'Вопрос',
            'answer' => 'Ответ',
        ]);
    }

    public static function find()
    {
        return new FaqQuery(get_called_class());
    }
}
