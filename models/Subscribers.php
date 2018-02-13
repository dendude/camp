<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%camp_subscribers}}".
 *
 * @property integer $id
 */
class Subscribers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%camp_subscribers}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ,
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
        ];
    }

    /**
     * @inheritdoc
     * @return SubscribersQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SubscribersQuery(get_called_class());
    }
}
