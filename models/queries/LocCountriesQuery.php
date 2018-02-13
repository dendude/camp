<?php

namespace app\models\queries;
use app\helpers\Statuses;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[LocCountries]].
 *
 * @see LocCountries
 */
class LocCountriesQuery extends ActiveQuery
{
    public function active() {
        return $this->andWhere(['{{%loc_countries}}.status' => Statuses::STATUS_ACTIVE]);
    }
    
    public function usage() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function ordering() {
        return $this->orderBy('ordering ASC, name ASC');
    }
}
