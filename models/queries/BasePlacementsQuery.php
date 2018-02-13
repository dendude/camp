<?php

namespace app\models\queries;

use app\helpers\Statuses;
use yii\db\ActiveQuery;

class BasePlacementsQuery extends ActiveQuery
{
    public function active() {
        return $this->andWhere(['status' => Statuses::STATUS_ACTIVE]);
    }
    
    public function using() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function byCamp($id) {
        return $this->andWhere(['camp_id' => $id]);
    }
    
    public function ordering() {
        return $this->orderBy(['id' => SORT_ASC]);
    }
}
