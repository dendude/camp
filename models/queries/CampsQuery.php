<?php
namespace app\models\queries;

use app\helpers\Statuses;
use yii\db\ActiveQuery;

class CampsQuery extends ActiveQuery
{
    public function waiting() {
        return $this->andWhere(['camp_camps.status' => Statuses::STATUS_DISABLED]);
    }
    
    public function active() {
        return $this->andWhere(['camp_camps.status' => Statuses::STATUS_ACTIVE]);
    }
    
    public function using() {
        return $this->andWhere('camp_camps.status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function recommend() {
        return $this->andWhere(['camp_camps.is_recommend' => Statuses::STATUS_ACTIVE]);
    }
    
    public function main() {
        return $this->andWhere(['camp_camps.is_main' => Statuses::STATUS_ACTIVE]);
    }

    public function rating() {
        return $this->andWhere(['camp_camps.is_rating' => Statuses::STATUS_ACTIVE]);
    }

    public function ordering() {
        return $this->orderBy(['camp_camps.ordering' => SORT_ASC, 'camp_camps.stars' => SORT_ASC]);
    }

    public function orderByRating() {
        return $this->orderBy(['camp_camps.is_rating' => SORT_DESC, 'stars' => SORT_DESC]);
    }
    
    public function byCountry($country_id) {
        return $this->joinWith('about')->andWhere(['camp_camps_about.loc_country' => $country_id]);
    }
    
    public function byRegion($region_id) {
        return $this->joinWith('about')->andWhere(['camp_camps_about.loc_region' => $region_id]);
    }
    
    public function byCity($city_id) {
        return $this->joinWith('about')->andWhere(['camp_camps_about.loc_city' => $city_id]);
    }
    
    public function byId($id) {
        return $this->andWhere(['id' => $id]);
    }
    
    public function byPartner($partner_id) {
        return $this->andWhere(['partner_id' => $partner_id]);
    }
    
    public function transferFrom($city_id) {
        return $this->joinWith('about')->andWhere(['like', 'camp_camps_about.trans_escort_cities', ",{$city_id},"]);
    }
    
    public function byName($name) {
        return $this->joinWith('about')->andFilterWhere(['like', 'camp_camps_about.name_short', $name]);
    }
    
    public function byType($type_id) {
        return $this->joinWith('about')->andWhere(['like', 'camp_camps_about.tags_types', ",{$type_id},"]);
    }
    
    public function byService($service_id) {
        return $this->joinWith('about')->andWhere(['like', 'camp_camps_about.tags_services', ",{$service_id},"]);
    }
    
    public function byGosCompensation() {
        return $this->joinWith('contract')->andWhere(['camp_camps_contract.opt_gos_compensation' => Statuses::STATUS_ACTIVE]);
    }
    
    public function byGroups() {
        return $this->joinWith('contract')->andWhere(['camp_camps_contract.opt_group_use' => Statuses::STATUS_ACTIVE]);
    }

    public function orderFree() {
        return $this->joinWith('media')->andWhere(['is_main' => Statuses::STATUS_ACTIVE])
            ->andWhere('camp_camps_media.photo_order_free <> ""');
    }
    
    public function byYears($age_from = null, $age_to = null) {
        if (is_numeric($age_from)) $this->joinWith('about')->andWhere(['<=', 'camp_camps_about.age_from', $age_from]);
        if (is_numeric($age_to))   $this->joinWith('about')->andWhere(['>=', 'camp_camps_about.age_to', $age_to]);
        return $this;
    }
    
    public function byDates($date_from = null, $date_to = null) {
        if (is_string($date_from)) $this->joinWith('items')->andWhere(['>=', 'camp_base_items.date_from', $date_from]);
        if (is_string($date_to))   $this->joinWith('items')->andWhere(['<=', 'camp_base_items.date_to', $date_to]);
        return $this;
    }
    
    public function orderByPrice($sort = 'ASC') {
        $this->orderBy(['min_price' => $sort]);
        return $this;
    }
}
