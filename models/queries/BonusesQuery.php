<?php
namespace app\models\queries;

use app\helpers\Statuses;
use yii\db\ActiveQuery;

class BonusesQuery extends ActiveQuery
{
    public function active() {
        return $this->andWhere(['status' => Statuses::STATUS_ACTIVE]);
    }
    
    public function ordering() {
        return $this->orderBy(['ordering' => SORT_ASC]);
    }
}
