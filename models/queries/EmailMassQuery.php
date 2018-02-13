<?php

namespace app\models\queries;
use app\helpers\Statuses;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[\app\models\EmailMass]].
 *
 * @see \app\models\EmailMass
 */
class EmailMassQuery extends ActiveQuery
{
    public function usage() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
}
