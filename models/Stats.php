<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%camp_stats}}".
 *
 * @property integer $id
 */
class Stats extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%camp_stats}}';
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
     * @return StatsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StatsQuery(get_called_class());
    }
}
