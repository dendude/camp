<?php

namespace app\models\queries;
use app\helpers\Statuses;

/**
 * This is the ActiveQuery class for [[Pages]].
 *
 * @see Pages
 */
class PagesQuery extends \yii\db\ActiveQuery
{
    public function using() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function byAlias($alias) {
        return $this->andWhere(['alias' => $alias]);
    }
}
