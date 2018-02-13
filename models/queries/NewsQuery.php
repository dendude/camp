<?php

namespace app\models\queries;
use app\helpers\Statuses;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[News]].
 *
 * @see News
 */
class NewsQuery extends ActiveQuery
{
    public function active() {
        return $this->andWhere(['status' => Statuses::STATUS_ACTIVE]);
    }
    
    public function usage() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function byAlias($alias) {
        return $this->andWhere(['alias' => $alias]);
    }
    
    public function ordering() {
        return $this->orderBy(['ordering' => SORT_ASC, 'id' => SORT_DESC]);
    }
}
