<?php
namespace app\models\queries;

use app\helpers\Statuses;
use yii\db\ActiveQuery;

class ReviewsItemsQuery extends ActiveQuery
{
    public function active() {
        return $this->andWhere(['status' => Statuses::STATUS_ACTIVE]);
    }
    
    public function using() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function ordering() {
        return $this->orderBy(['ordering' => SORT_ASC, 'id' => SORT_DESC]);
    }
}
