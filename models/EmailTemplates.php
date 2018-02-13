<?php

namespace app\models;

use app\helpers\Normalize;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%email_templates}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $subject
 * @property string $content
 * @property integer $created
 * @property integer $modified
 */
class EmailTemplates extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%email_templates}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manager_id', 'subject', 'content', 'created'], 'required'],
            [['manager_id', 'created', 'modified'], 'integer'],
            [['manager_id', 'created', 'modified'], 'default', 'value' => 0],
            [['content'], 'string'],
            [['subject'], 'string', 'max' => 200]
        ];
    }

    public function getAuthor() {
        return $this->hasOne(Users::className(), ['id' => 'manager_id']);
    }

    public function beforeValidate()
    {
        $this->manager_id = Yii::$app->user->id;
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        return parent::beforeValidate();
    }

    public static function getFilterList() {
        $list = self::find()->orderBy(['ordering' => SORT_ASC])->all();
        return $list ? ArrayHelper::map($list, 'id', 'subject') : [];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'subject' => 'Тема',
            'content' => 'Содержимое',
        ]);
    }
}
