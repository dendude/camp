<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\LocCitiesQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%loc_cities}}".
 *
 * @property integer $id
 * @property integer $country_id
 * @property integer $region_id
 * @property string $name
 * @property string $alias
 * @property integer $created
 * @property integer $modified
 * @property integer $ordering
 * @property integer $status
 * @property integer $manager_id
 *
 * @property LocCountries $country
 * @property LocRegions $region
 */
class LocCities extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loc_cities}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'country_id', 'region_id', 'created', 'manager_id'], 'required'],
            
            [['country_id', 'region_id', 'created', 'modified', 'ordering', 'status', 'manager_id'], 'integer'],
            [['country_id', 'region_id', 'created', 'modified', 'ordering', 'status', 'manager_id'], 'default', 'value' => 0],
            
            [['name', 'alias'], 'string', 'max' => 100],
            [['name', 'alias'], 'default', 'value' => ''],

            ['alias', 'unique'],
        ];
    }
    
    public function getCountry() {
        return $this->hasOne(LocCountries::className(), ['id' => 'country_id']);
    }
    
    public function getRegion() {
        return $this->hasOne(LocRegions::className(), ['id' => 'region_id']);
    }
    
    public static function getFilterList($region_id, $full = false) {
        if (empty($region_id)) return [];
        
        $query = self::find()->byRegion($region_id)->ordering();
        if ($full) {
            $query->usage();
        } else {
            $query->active();
        }
        
        $list = $query->all();
        return $list ? ArrayHelper::map($list, 'id', 'name') : [];
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
    
        if (php_sapi_name() != "cli") $this->manager_id = Yii::$app->user->id;
        if (empty($this->alias)) $this->alias = Normalize::alias($this->name);
        
        return parent::beforeValidate();
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'country_id' => 'Страна',
            'region_id' => 'Регион',
            'name' => 'Название',
        ]);
    }

    public static function find()
    {
        return new LocCitiesQuery(get_called_class());
    }
}
