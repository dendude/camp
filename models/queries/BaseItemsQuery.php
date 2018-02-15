<?php

namespace app\models\queries;
use app\helpers\Statuses;
use app\models\TagsTypes;
use yii\db\ActiveQuery;

class BaseItemsQuery extends ActiveQuery
{
    public function waiting()
    {
        return $this->andWhere(['status' => Statuses::STATUS_NEW]);
    }
    
    public function active()
    {
        return $this->andWhere('status = :status AND date_from >= CURDATE()', [':status' => Statuses::STATUS_ACTIVE]);
    }
    
    public function using()
    {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function byCamp($id)
    {
        return $this->andWhere(['camp_id' => $id]);
    }
    
    public function byPartner($id)
    {
        return $this->andWhere(['partner_id' => $id]);
    }
    
    public function ordering()
    {
        // попросили убрать сортировку по дате и выводить как вводили
        $this->orderBy(['id' => SORT_ASC]);
        
        return $this;
    }
}
