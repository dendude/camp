<?php

namespace app\models;

use app\helpers\Normalize;
use app\helpers\Statuses;
use app\models\queries\EmailMassQuery;
use Yii;

/**
 * This is the model class for table "{{%email_mass}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $title
 * @property string $comment
 * @property string $content
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 * @property integer $send_time
 * @property integer $count_total
 * @property integer $count_sent
 */
class EmailMass extends \yii\db\ActiveRecord
{
    public $send_now = false;
    public $send_date;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%email_mass}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manager_id', 'created', 'title', 'content'], 'required'],
            
            [['manager_id', 'created', 'modified', 'status', 'send_time', 'count_total', 'count_sent'], 'integer'],
            [['manager_id', 'created', 'modified', 'status', 'send_time', 'count_total', 'count_sent'], 'default', 'value' => 0],
            
            [['title'], 'string', 'max' => 100],
            [['comment'], 'string', 'max' => 250],
            [['content'], 'string'],
            
            ['send_now', 'boolean'],
            ['send_date', 'required', 'when' => function(self $model){
                return !$model->send_now;
            }],
            ['send_date', 'date', 'format' => 'dd.MM.yyyy HH:mm', 'when' => function(self $model){
                return !$model->send_now;
            }],
        ];
    }
    
    public function isProcessed() {
        return in_array($this->status, [Statuses::STATUS_USED, Statuses::STATUS_ACTIVE]);
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        $this->manager_id = Yii::$app->user->id;
        $this->content = str_replace(' style=""', '', $this->content);
        
        if ($this->send_now) {
            $this->send_time = 0;
        } else {
            $this->send_now = false;
            $this->send_time = strtotime($this->send_date);
        }
        
        return parent::beforeValidate();
    }
    
    public function afterFind()
    {
        if ($this->send_time) {
            $this->send_date = date('d.m.Y H:i', $this->send_time);
        } else {
            $this->send_now = true;
        }
        
        parent::afterFind();
    }
    
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'title' => 'Заголовок письма',
            'comment' => 'Комментарий',
            'content' => 'Содержимое письма',
            'send_time' => 'Время отправки',
            'count_total' => 'Всего',
            'count_sent' => 'Отправлено',
            
            'send_now' => 'Отправить немедленно',
            'send_date' => 'Время отправки',
        ]);
    }

    /**
     * @inheritdoc
     * @return \app\models\queries\EmailMassQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new EmailMassQuery(get_called_class());
    }
}
