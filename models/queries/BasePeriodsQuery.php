<?php

namespace app\models\queries;
use app\helpers\Statuses;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[BasePeriods]].
 *
 * @see BasePeriods
 */
class BasePeriodsQuery extends ActiveQuery
{
    public function using() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function byCamp($camp_id) {
        return $this->andWhere(['camp_id' => $camp_id]);
    }
    
    public function ordering() {
        return $this->orderBy(['date_from' => SORT_ASC, 'date_to' => SORT_ASC]);
    }
}
