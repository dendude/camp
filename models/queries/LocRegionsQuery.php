<?php

namespace app\models\queries;
use app\helpers\Statuses;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[LocRegions]].
 *
 * @see LocRegions
 */
class LocRegionsQuery extends ActiveQuery
{
    public function active() {
        return $this->andWhere(['status' => Statuses::STATUS_ACTIVE]);
    }
    
    public function usage() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function byCountry($country_id) {
        return $this->andWhere(['country_id' => $country_id]);
    }
    
    public function ordering() {
        return $this->orderBy('ordering ASC, name ASC');
    }
}
