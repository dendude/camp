<?php
namespace app\models\queries;

use app\helpers\Statuses;
use Yii;
use yii\db\ActiveQuery;

class OrdersQuery extends ActiveQuery
{
    public function waiting()
    {
        return $this->andWhere(['status' => Statuses::STATUS_NEW]);
    }
    
    public function using()
    {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }
    
    public function byPartner($partner_id)
    {
        return $this->andWhere(['partner_id' => $partner_id]);
    }
    
    public function byUser($user_id)
    {
        return $this->andWhere(['user_id' => $user_id]);
    }
    
    public function byItem($item_id)
    {
        return $this->andWhere(['item_id' => $item_id]);
    }
    
    public function bySelf()
    {
        return $this->byUser(Yii::$app->user->id);
    }
}
