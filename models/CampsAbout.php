<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\helpers\Normalize;
use app\models\queries\CampsAboutQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * This is the model class for table "{{%camps_about}}".
 *
 * @property integer $id
 * @property integer $camp_id
 * @property string $name_short
 * @property string $name_full
 * @property string $name_org
 * @property string $name_variants
 * @property string $name_details
 * @property string $tags_types
 * @property string $tags_sport
 * @property string $tags_places
 * @property string $tags_services
 * @property integer $count_builds
 * @property integer $loc_country
 * @property integer $loc_region
 * @property integer $loc_city
 * @property string $loc_address
 * @property string $loc_routing
 * @property integer $loc_distance_to_city
 * @property string $loc_coords
 * @property integer $trans_in_price
 * @property integer $trans_with_escort
 * @property string $trans_escort_cities
 * @property integer $count_per_year
 * @property integer $count_places
 * @property string $area
 * @property string $made_year
 * @property integer $age_to
 * @property integer $age_from
 *
 * @property LocCountries $country
 * @property LocRegions $region
 * @property LocCities $city
 */
class CampsAbout extends ActiveRecord
{
    public $tags_types_f = [];
    public $tags_sport_f = [];
    public $tags_places_f = [];
    public $tags_services_f = [];
    public $trans_escort_cities_f = [];
    
    // данные геопозиции объекта, по умолчанию Москва
    public $loc_coords_f = ['lat' => 55.76, 'lng' => 37.64, 'zoom' => 12];
    
    const SCENARIO_ADMIN = 'admin';
    
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADMIN] = $scenarios[self::SCENARIO_DEFAULT];
        
        return parent::scenarios();
    }
    
    public static function tableName()
    {
        return '{{%camps_about}}';
    }

    public function rules()
    {
        return [
            [['loc_country', 'loc_region', 'loc_city', 'count_per_year', 'count_places',
              'age_to', 'age_from', 'loc_address', 'name_short', 'name_full'], 'required', 'on' => self::SCENARIO_DEFAULT],
    
            [['loc_country', 'loc_region', 'loc_city',
              'age_to', 'age_from', 'name_short', 'name_full'], 'required', 'on' => self::SCENARIO_ADMIN],
            
            [['camp_id', 'count_builds', 'loc_country', 'loc_region', 'loc_city', 'loc_distance_to_city',
              'trans_in_price', 'trans_with_escort', 'count_per_year', 'count_places', 'age_to', 'age_from'], 'integer'],

            [['age_from', 'age_to'], 'integer', 'min' => 1, 'max' => 30],
            [['count_builds'], 'integer', 'min' => 1, 'max' => 100],
            [['area'], 'number', 'min' => 0.1, 'max' => 9999, 'numberPattern' => '/^\s*[-+]?[0-9]*[\.,]?[0-9]+([eE][-+]?[0-9]+)?\s*$/'],
            [['made_year'], 'integer', 'min' => 1900, 'max' => date('Y')],
            
            [['name_short', 'name_org', 'loc_address', 'loc_coords', 'trans_escort_cities'], 'string', 'max' => 200],
            [['name_short', 'name_org', 'loc_address', 'loc_coords', 'trans_escort_cities'], 'default', 'value' => ''],
            
            [['name_full', 'name_variants', 'name_details', 'loc_routing',
              'tags_types', 'tags_sport', 'tags_places', 'tags_services'], 'string', 'max' => 500],

            [['tags_types_f', 'tags_sport_f', 'tags_places_f',
              'tags_services_f', 'trans_escort_cities_f'], 'each', 'rule' => ['integer']],
            
            
            [['age_to'], 'compare', 'compareAttribute' => 'age_from', 'operator' => '>=', 'enableClientValidation' => false],

            [['loc_coords_f'], 'each', 'rule' => ['number']],

            ['tags_types_f', 'required', 'message' => 'Укажите типы лагеря'],
            ['tags_types_f', 'checkCount', 'params' => ['min' => 1, 'max' => 5], 'on' => self::SCENARIO_DEFAULT],

            ['tags_sport_f', 'required', 'message' => 'Укажите виды спорта', 'on' => self::SCENARIO_DEFAULT],
            ['tags_places_f', 'required', 'message' => 'Укажите объекты инфраструктуры лагеря', 'on' => self::SCENARIO_DEFAULT],
            ['tags_services_f', 'required', 'message' => 'Укажите удобства и услуги в лагере', 'on' => self::SCENARIO_DEFAULT],
        ];
    }
    
    public function checkCount($attribute, $params)
    {
        $amount_words = ['опций', 'опцию', 'опции'];
        
        if (count($this->{$attribute}) < $params['min']) {
            $word_count = Normalize::wordAmount($params['min'], $amount_words, true);
            $this->addError($attribute, 'Выберите не менее ' . $word_count);
        } elseif (count($this->{$attribute}) > $params['max']) {
            $word_count = Normalize::wordAmount($params['max'], $amount_words, true);
            $this->addError($attribute, 'Выберите не более ' . $word_count);
        }
    }
    
    public function beforeValidate()
    {
        // позволяем ввести 2.5; 2,5
        $this->area = str_replace(',', '.', trim($this->area));
        
        // координаты в строку
        $this->loc_coords = Json::encode($this->loc_coords_f);
    
        // фильтруем от лишних пробелов
        $variants = array_filter(explode(',', $this->name_variants));
        foreach ($variants AS &$v) $v = trim($v);
        $this->name_variants = implode(',', $variants);
    
        // переводим массивы в строки
        foreach (['tags_types', 'tags_sport', 'tags_places', 'tags_services', 'trans_escort_cities'] AS $name) {
            $name_f = "{$name}_f";
            if (!empty($this->{$name_f}) && is_array($this->{$name_f})) {
                $this->{$name_f} = array_filter($this->{$name_f});
                if (count($this->{$name_f})) {
                    $this->{$name} = ',' . implode(',', $this->{$name_f}) . ',';
                }
            }
        }
        
        return parent::beforeValidate();
    }
    
    public function afterFind()
    {
        // переводим строки в массивы
        foreach (['tags_types', 'tags_sport', 'tags_places', 'tags_services'] AS $name) {
            $name_f = "{$name}_f";
            if ($this->{$name}) {
                $this->{$name_f} = explode(',', trim($this->{$name}, ','));
            }
        }

        if ($this->trans_escort_cities) {
            $ids = explode(',', trim($this->trans_escort_cities, ','));
            $cities = LocCities::find()->where(['id' => $ids])->all();
            $this->trans_escort_cities_f = ArrayHelper::map($cities, 'id', 'name');
        }
    
        // координаты в массив
        if ($this->loc_coords) {
            $this->loc_coords_f = Json::decode($this->loc_coords);
        }
        
        parent::afterFind();
    }
    
    public function isForGroups() {
        return in_array(TagsTypes::GROUPS_ID, $this->tags_types_f);
    }
    
    public function getCountry() {
        return $this->hasOne(LocCountries::className(), ['id' => 'loc_country']);
    }
    public function getRegion() {
        return $this->hasOne(LocRegions::className(), ['id' => 'loc_region']);
    }
    public function getCity() {
        return $this->hasOne(LocCities::className(), ['id' => 'loc_city']);
    }
    
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'name_short' => 'Название лагеря',
            'name_full' => 'Юридическое лицо',
            'name_org' => 'Организатор',
            'name_variants' => 'Варианты названий',
            'name_details' => 'Краткое описание',
    
            'tags_types' => 'Тип лагеря',
            'tags_types_f' => 'Тип лагеря',
            
            'tags_sport' => 'Виды спорта',
            'tags_sport_f' => 'Виды спорта',
            
            'tags_places' => 'Инфраструктура',
            'tags_places_f' => 'Инфраструктура',
            
            'tags_services' => 'Удобства и услуги',
            'tags_services_f' => 'Удобства и услуги',

            'trans_escort_cities' => 'Выезд с сопровождением',
            'trans_escort_cities_f' => 'Выезд с сопровождением',
                    
            'loc_country' => 'Страна',
            'loc_region' => 'Регион',
            'loc_city' => 'Ближайший город',
            'loc_address' => 'Адрес лагеря',
            'loc_routing' => 'Маршрут',
            'loc_distance_to_city' => 'Расстояние до города',
            'loc_coords' => 'Показать на карте',

            'escort_cities' => 'Выезд с сопровождением',
            'trans_in_price' => 'Транспортировка входит в стоимость',
            'trans_with_escort' => 'Транспортировка с сопровождением',
            
            'count_per_year' => 'Ежегодно посещают',
            'count_places' => 'Кол-во мест в лагере',
            
            'area' => 'Площадь лагеря',
            'made_year' => 'Лагерь основан в',
            'count_builds' => 'Кол-во корпусов',
            
            'age_to' => 'Возраст до',
            'age_from' => 'Возраст от',
        ]);
    }

    public static function find()
    {
        return new CampsAboutQuery(get_called_class());
    }
}
