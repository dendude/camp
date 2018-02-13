<?php
namespace app\models\forms;

use app\models\Camps;
use app\models\queries\CampsQuery;
use yii\base\Model;
use yii\data\Pagination;

/**
 * форма опций к лагерю
 *
 * Class CampOpts
 * @package app\models\forms
 */
class SearchForm extends Model
{
    public $country_id;
    public $region_id;
    public $city_from;
    public $service;
    
    public $date;
    public $ages;
    public $type;
    public $name;
    
    public $compensation;
    
    public static function getAges() {
        return [
            '0-6' => 'До 6 лет',
            '6-7' => '6-7 лет',
            '8-9' => '8-9 лет',
            '10-11' => '10-11 лет',
            '12-13' => '12-13 лет',
            '14-15' => '14-15 лет',
            '16-17' => '16-17 лет',
            '18-25' => 'от 18 лет',
        ];
    }
    
    public static function getDates() {
        $cur_month = date('m');
        $cur_sezon = ceil($cur_month / 3);
        
        $names = [
            1 => 'Зима',
            2 => 'Весна',
            3 => 'Лето',
            4 => 'Осень',
        ];
    
        $sezon = [
            1 => ['Декабрь','Январь','Февраль'],
            2 => ['Март','Апрель','Май'],
            3 => ['Июнь','Июль','Август'],
            4 => ['Сентябрь','Октябрь','Ноябрь'],
        ];
        
        $result = [];
        
        foreach ($sezon[$cur_sezon] AS $k => $m) {
            if ($cur_month <= (($cur_sezon - 1)*3 + $k)) {
                $dt = date('Y') . '-' . sprintf('%02d', ($cur_sezon - 1)*3 + $k) . '-01_' . date('Y') . '-' . sprintf('%02d', ($cur_sezon - 1)*3 + $k) . '-31';
                
                $result[$dt] = $m . ' - ' . date('Y') . '"';
            }
        }
        
        // сезоны текущего года, не включая сезон текущего месяца
        foreach ($names AS $k => $name) {
            if ($cur_month < (($k-1)*3) ) {
                $dt = date('Y') . '-' . sprintf('%02d', ($k-1)*3) . '-01_' . date('Y') . '-' . sprintf('%02d', $k*3-1) . '-31';
                
                $result[$dt] = mb_strtoupper($name, \Yii::$app->charset) . ' - ' . date('Y') . '"';
            }
        }
        
        // сезоны будущего года
        foreach ($names AS $k => $name) {
            if ($k == 1) {
                $dt = date('Y') . '-12-01_' . (date('Y') + 1) . '-02-31';
                $result[$dt] = mb_strtoupper($name, \Yii::$app->charset) . ' - ' . date('Y') . '/' . (date('y') + 1) . '"';
            } else {
                $dt = (date('Y') + 1) . '-' . sprintf('%02d', (($k-1)*3)) . '-01_' . (date('Y') + 1) . '-' . sprintf('%02d', ($k*3-1)) . '-31';
                $result[$dt] = mb_strtoupper($name, \Yii::$app->charset) . ' - ' . (date('Y') + 1) . '"';
            }
        }
        
        return $result;
    }
    
    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['country_id', 'region_id', 'city_from', 'type', 'service'], 'integer'],
            [['ages', 'date', 'name'], 'string'],
            ['compensation', 'boolean'],
        ];
    }

    /**
     * @return array
     */
    public function search() {
        /** @var $query CampsQuery */
        $query = Camps::find()->active()->orderBy(['ordering' => SORT_ASC]);

        if ($this->country_id) $query->byCountry($this->country_id);
        if ($this->region_id) $query->byRegion($this->region_id);
        if ($this->city_from) $query->transferFrom($this->city_from);
        if ($this->type) $query->byType($this->type);
        if ($this->service) $query->byService($this->service);
        if ($this->compensation) $query->byGosCompensation();

        $query->joinWith('about')->andFilterWhere(['like', 'camp_camps_about.name_full', $this->name]);
    
        if ($this->ages) {
            @list($age_from, $age_to) = explode('-', $this->ages);
            $query->byYears($age_from, $age_to);
        }

        $countQuery = clone $query;

        $pages = new Pagination([
            'totalCount' => $countQuery->count(),
            'pageSize' => 10
        ]);
        $models = $query->offset($pages->offset)->limit($pages->limit)->all();

        return ['models' => $models, 'pages' => $pages];
    }

    public function attributeLabels() {
        return [
            'country_id' => 'Страна',
            'region_id' => 'Регион',
            'city_from' => 'Выезд из',
            'service' => 'Удобства и услуги',
            
            'date' => 'Смена/Сезон',
            'ages' => 'Возраст',
            'type' => 'Тип лагеря',
            'name' => 'Название лагеря',

            'compensation' => 'Госкомпенсация',
        ];
    }
}