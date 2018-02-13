<?php
namespace app\models\queries;

use app\helpers\Statuses;
use yii\db\ActiveQuery;

class ChangesQuery extends ActiveQuery
{
    public function waiting() {
        return $this->andWhere(['status' => Statuses::STATUS_NEW]);
    }
    
    public function using() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function ordering() {
        return $this->orderBy(['id' => SORT_ASC]);
    }
}
