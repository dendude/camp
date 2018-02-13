<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%camp_users_socials}}".
 *
 * @property integer $id
 */
class UsersSocials extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%camp_users_socials}}';
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
     * @return UsersSocialsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UsersSocialsQuery(get_called_class());
    }
}
