<?php
namespace app\models;

use app\helpers\Statuses;
use app\models\queries\BaseItemsQuery;
use app\traits\TraitBeforeValidate;
use Yii;
use yii\db\ActiveRecord;
use app\helpers\Normalize;
use app\models\queries\CampsQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%camps}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $partner_id
 * @property string $alias
 * @property string $stars
 * @property string $meta_t
 * @property string $meta_d
 * @property string $meta_k
 * @property integer $incamp_id
 * @property string $incamp_url
 * @property integer $is_new
 * @property integer $is_leader
 * @property integer $is_rating
 * @property integer $is_recommend
 * @property integer $is_vip
 * @property integer $is_main
 * @property integer $created
 * @property integer $modified
 * @property integer $ordering
 * @property integer $min_price
 * @property integer $status
 *
 * @property CampsAbout $about
 * @property CampsClient $client
 * @property CampsContacts $contacts
 * @property CampsContract $contract
 * @property CampsMedia $media
 * @property CampsPlacement $placement
 *
 * @property BaseItems[] $items
 * @property BaseItems[] $itemsActive
 * @property BasePlacements[] $placements
 */
class Camps extends ActiveRecord
{
    use TraitBeforeValidate;

    // для формирования ссылок
    const TYPE_COUNTRY = 'country';
    const TYPE_REGION = 'region';
    const TYPE_CITY = 'city';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_TYPE = 'type';
    const TYPE_SERVICE = 'service';
    const TYPE_COMPENSATION = 'compensation';
    const TYPE_GROUPS = 'groups';
    const TYPE_YEARS = 'years';
        
    public static function tableName()
    {
        return '{{%camps}}';
    }

    public function rules()
    {
        return [
            [['manager_id', 'partner_id', 'incamp_id', 'is_new', 'is_leader', 'is_rating', 'is_recommend',
              'is_vip', 'is_main', 'created', 'modified', 'ordering', 'min_price', 'status'], 'integer'],

            [['manager_id', 'partner_id', 'incamp_id', 'is_new', 'is_leader', 'is_rating', 'is_recommend',
              'is_vip', 'is_main', 'created', 'modified', 'ordering', 'min_price', 'status'], 'default', 'value' => 0],
            
            [['stars'], 'number'],
            [['stars'], 'default', 'value' => 0],
            
            [['alias'], 'string', 'max' => 200],
            [['meta_t', 'meta_d', 'meta_k'], 'string', 'max' => 255],
            [['incamp_url'], 'string', 'max' => 500],
        ];
    }
    
    public function getItems() {
        return $this->hasMany(BaseItems::className(), ['camp_id' => 'id'])->ordering();
    }
    
    public function getItemsActive() {
        return $this->hasMany(BaseItems::className(), ['camp_id' => 'id'])->active()->ordering();
    }

    public function getPlacements() {
        return $this->hasMany(BasePlacements::className(), ['camp_id' => 'id'])->using()->ordering();
    }
    
    public function getAbout()
    {
        return $this->hasOne(CampsAbout::className(), ['camp_id' => 'id']);
    }
    public function getClient()
    {
        return $this->hasOne(CampsClient::className(), ['camp_id' => 'id']);
    }
    public function getContacts()
    {
        return $this->hasOne(CampsContacts::className(), ['camp_id' => 'id']);
    }
    public function getContract()
    {
        return $this->hasOne(CampsContract::className(), ['camp_id' => 'id']);
    }
    public function getMedia()
    {
        return $this->hasOne(CampsMedia::className(), ['camp_id' => 'id']);
    }
    public function getPlacement()
    {
        return $this->hasOne(CampsPlacement::className(), ['camp_id' => 'id']);
    }
        
    public function getAgesText() {
        return "Для детей {$this->about->age_from} - {$this->about->age_to} лет";
    }
    
    public function getCampUrl($scheme = false) {
        return Url::to(["/{$this->about->country->alias}/{$this->about->region->alias}/camp/{$this->alias}-{$this->id}"], $scheme);
    }
    
    public function getTagsTypes() {
        $list = TagsTypes::findAll($this->about->tags_types_f);
        return $list ? ArrayHelper::map($list, 'id', 'title') : [];
    }
    
    public function getTagsPlaces() {
        $list = TagsPlaces::findAll($this->about->tags_places_f);
        return $list ? ArrayHelper::map($list, 'id', 'title') : [];
    }
    
    public function getTagsServices() {
        $list = ComfortTypes::findAll($this->about->tags_services_f);
        return $list ? ArrayHelper::map($list, 'id', 'title') : [];
    }
    
    public function getTagsSport() {
        $list = TagsSport::findAll($this->about->tags_sport_f);
        return $list ? ArrayHelper::map($list, 'id', 'title') : [];
    }

    public function getFullName() {
        return $this->about->name_short . PHP_EOL . $this->about->name_full . " [{$this->about->country->name}/{$this->about->region->name}]";
    }

    public static function getFilterList($full = false, $partner_id = null) {
        $query = self::find()->ordering();
        if ($full !== true) $query->active();
        if (is_numeric($partner_id)) $query->byPartner($partner_id);

        $list = $query->all();
        return $list ? ArrayHelper::map($list, 'id', function(self $model){
            return $model->getFullName();
        }) : [];
    }
    
    public function getEscortCities() {
        $cities = LocCities::find()->where([
            'id' => array_keys($this->about->trans_escort_cities_f)
        ])->active()->ordering()->all();
        return $cities ? ArrayHelper::map($cities, 'id', 'name') : [];
    }

    public static function getEscortCitiesFilter($country_id) {
        /** @var $bases self[] */
        $bases = self::find()->joinWith('about')
            ->select('camp_camps_about.id, camp_camps_about.trans_escort_cities')
            ->andWhere("camp_camps_about.trans_escort_cities <> ''")
            ->active()->all();
        if ($bases) {
            $cities_ids = [];
            foreach ($bases AS $base) $cities_ids += $base->about->trans_escort_cities_f;

            $cities = LocCities::find()->where(['id' => $cities_ids])->byCountry($country_id)->active()->ordering()->all();
            return ArrayHelper::map($cities, 'id', 'name');
        }

        return [];
    }

    public function getReviewsTotalText() {
        switch (true) {
            case ($this->stars >= 9) :
                $result = ReviewsItems::getVoteName(5);
                $class = 'text-success';
                break;

            case ($this->stars >= 7 && $this->stars < 9) :
                $result = ReviewsItems::getVoteName(4);
                $class = 'text-success';
                break;

            case ($this->stars >= 4 && $this->stars < 7) :
                $result = ReviewsItems::getVoteName(3);
                $class = 'text-warning';
                break;

            case ($this->stars >= 3 && $this->stars < 4) :
                $result = ReviewsItems::getVoteName(2);
                $class = 'text-danger';
                break;

            default:
                $result = ReviewsItems::getVoteName(1);
                $class = 'text-danger';
        }

        return Html::tag('strong', $result, ['class' => $class]);
    }
    
    public function getPhotos() {
    
        $photos_arr = [];
            
        foreach (['photos_room', 'photos_sport', 'photos_concert_hall', 'photos_eating',
                  'photos_comfort', 'photos_med', 'photos_security', 'photos_area', 'photos_others'] AS $f) {
            if ($this->media->{$f}) {
                $photos_arr = array_merge($photos_arr, Json::decode($this->media->{$f}, true));
            }
        }
        
        $photos = [];
        $photos['main'] = $this->media->photo_main;
        $photos['partner'] = $this->media->photo_partner;
        $photos['list'] = $photos_arr;
        
        return $photos;
    }
    
    public function beforeValidate()
    {
        $this->traitBeforeValidate();
        
        if ($this->isNewRecord) {
            if (Yii::$app->user->id) $this->partner_id = Yii::$app->user->id;
        }
        
        return parent::beforeValidate();
    }
    
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if ($this->itemsActive) {
            // для удобной сортировки
            $min_price = $this->itemsActive[0]->getCurrentPrice();
            
            foreach ($this->itemsActive AS $i) {
                if ($min_price > $i->getCurrentPrice()) {
                    $min_price = $i->getCurrentPrice();
                }
            }
            
            $this->updateAttributes(['min_price' => $min_price]);
        }
    }
    
    public function afterFind()
    {
        // if (0.0) - возвращает true
        if ($this->stars < 0.1) $this->stars = 0;
        
        parent::afterFind();
    }
    
    public function setDeleteStatus()
    {
        $this->updateAttributes(['status' => Statuses::STATUS_REMOVED]);
    
        BaseItems::updateAll(['status' => Statuses::STATUS_REMOVED], ['camp_id' => $this->id]);
        BasePeriods::updateAll(['status' => Statuses::STATUS_REMOVED], ['camp_id' => $this->id]);
        BasePlacements::updateAll(['status' => Statuses::STATUS_REMOVED], ['camp_id' => $this->id]);
    }
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'partner_id' => 'Партнер',
            'stars' => 'Stars',
            
            'incamp_id' => 'Incamp ID',
            'incamp_url' => 'Incamp Url',
            
            'is_main' => 'На главной в Забронировать бесплатно',
            'is_recommend' => 'Отображать в подборках',
            'is_rating' => 'Рейтинговое',

            'is_vip' => 'VIP',
            'is_leader' => 'Лидер продаж',
            'is_new' => 'Новый',
        ]);
    }

    public static function find()
    {
        return new CampsQuery(get_called_class());
    }
}
