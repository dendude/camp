<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\BasePeriodsQuery;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%base_periods}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $partner_id
 * @property integer $camp_id
 * @property string $date_from
 * @property string $date_to
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 */
class BasePeriods extends ActiveRecord
{
    public $date_from_orig;
    public $date_to_orig;
    
    public static function tableName()
    {
        return '{{%base_periods}}';
    }

    public function rules()
    {
        return [
            [['created', 'date_from', 'date_to', 'date_from_orig', 'date_to_orig'], 'required'],
            
            [['manager_id', 'partner_id', 'camp_id', 'created', 'modified', 'status'], 'integer'],
            [['manager_id', 'partner_id', 'camp_id', 'created', 'modified', 'status'], 'default', 'value' => 0],
            
            [['date_from', 'date_to'], 'date', 'format' => 'yyyy-MM-dd'],
            [['date_from_orig', 'date_to_orig'], 'date', 'format' => 'dd.MM.yyyy'],
            
            ['date_to', 'compare', 'compareAttribute' => 'date_from', 'operator' => '>', 'when' => function(self $model){
                return ($model->date_from && $model->date_to);
            }],
        ];
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
            if (Users::isPartner() && !$this->partner_id) $this->partner_id = Yii::$app->user->id;
        } else {
            $this->modified = time();
        }
        
        if (Users::isAdmin()) $this->manager_id = Yii::$app->user->id;
        
        if ($this->date_from_orig) $this->date_from = Normalize::getSqlDate($this->date_from_orig);
        if ($this->date_to_orig) $this->date_to = Normalize::getSqlDate($this->date_to_orig);
        
        return parent::beforeValidate();
    }
    
    public function getAttributes($names = null, $except = [])
    {
        $attributes = parent::getAttributes($names, $except);
        $attributes['date_from_orig'] = $this->date_from_orig;
        $attributes['date_to_orig'] = $this->date_to_orig;
        
        return $attributes;
    }
    
    public function afterFind()
    {
        $this->date_from_orig = Normalize::getDate($this->date_from);
        $this->date_to_orig = Normalize::getDate($this->date_to);
        
        parent::afterFind();
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'partner_id' => 'Партнер',
            'camp_id' => 'Лагерь',
            'date_from' => 'Дата с',
            'date_to' => 'Дата по',
            'date_from_orig' => 'Дата с',
            'date_to_orig' => 'Дата по',
        ]);
    }

    public static function find()
    {
        return new BasePeriodsQuery(get_called_class());
    }
}
