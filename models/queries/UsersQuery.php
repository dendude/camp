<?php

namespace app\models\queries;
use app\helpers\Statuses;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Users]].
 *
 * @see Users
 */
class UsersQuery extends ActiveQuery
{
    public function active() {
        return $this->andWhere(['status' => Statuses::STATUS_ACTIVE]);
    }

    public function using() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
}
