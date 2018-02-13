<?php

namespace app\models;

use app\helpers\Normalize;
use app\helpers\Statuses;
use app\models\queries\LocCountriesQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%loc_countries}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $name_in
 * @property string $alias
 * @property string $content
 *
 * @property string $meta_t
 * @property string $meta_d
 * @property string $meta_k
 *
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 * @property integer $ordering
 * @property integer $manager_id
 */
class LocCountries extends ActiveRecord
{
    const DEFAULT_ID = 1; // russia
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loc_countries}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'name_in', 'alias', 'created', 'manager_id'], 'required'],
            
            [['created', 'modified', 'status', 'ordering', 'manager_id'], 'integer'],
            [['created', 'modified', 'status', 'ordering', 'manager_id'], 'default', 'value' => 0],
            
            [['name', 'name_in', 'alias'], 'string', 'max' => 100],
            [['name', 'name_in', 'alias'], 'default', 'value' => ''],

            [['meta_t', 'meta_d', 'meta_k'], 'string', 'max' => 250],
            [['meta_t', 'meta_d', 'meta_k'], 'default', 'value' => ''],

            ['alias', 'unique'],
            
            ['content', 'string'],
        ];
    }
        
    public static function getFilterList($full = false) {
        $query = self::find()->ordering();
        if ($full) {
            $query->usage();
        } else {
            $query->active();
        }
        
        $list = $query->all();
        return $list ? ArrayHelper::map($list, 'id', 'name') : [];
    }
    
    public static function getAliasById($country_id)
    {
        $model = self::findOne($country_id);
        return $model ? $model->alias : null;
    }
    
    public static function getFilterListWithCamps() {
        $ids = Camps::find()->joinWith('about')
            ->select('camp_camps_about.loc_country')
            ->active()->distinct()->column();

        $list = self::find()->where(['id' => $ids])
            ->active()->ordering()->all();
    
        return $list ? ArrayHelper::map($list, 'id', function(self $model){
            return $model->name . ' [' . Camps::find()->byCountry($model->id)->active()->count() .  ']';
        }) : [];
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
    
        $this->manager_id = Yii::$app->user->id;
        $this->alias = Normalize::alias($this->name);
        
        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'name' => 'Название',
            'name_in' => 'Название (где)',
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new LocCountriesQuery(get_called_class());
    }
}
