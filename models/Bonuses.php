<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\BasePeriodsQuery;
use app\models\queries\BonusesQuery;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%bonuses}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $icon_class
 * @property string $icon_color
 * @property string $sys_name
 * @property string $site_name
 * @property integer $bonuses
 * @property integer $ordering
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 */
class Bonuses extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%bonuses}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manager_id', 'sys_name', 'site_name', 'bonuses', 'created'], 'required'],
            [['icon_class', 'icon_color'], 'required'],
            
            [['manager_id', 'bonuses', 'created', 'modified', 'status', 'ordering'], 'integer'],
            [['manager_id', 'bonuses', 'created', 'modified', 'status', 'ordering'], 'default', 'value' => 0],
            
            [['sys_name', 'site_name'], 'string', 'max' => 100],
            [['icon_class', 'icon_color'], 'string', 'max' => 50],
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
        
        return parent::beforeValidate();
    }
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'icon_class' => 'Иконка',
            'icon_color' => 'Цвет иконки',
            'bonuses' => 'Бонусов',
            'sys_name' => 'Системное название',
            'site_name' => 'Название на сайте',
        ]);
    }

    public static function find()
    {
        return new BonusesQuery(get_called_class());
    }
}
