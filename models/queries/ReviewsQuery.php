<?php
namespace app\models\queries;

use Yii;
use app\helpers\Statuses;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Reviews]].
 *
 * @see Reviews
 */
class ReviewsQuery extends ActiveQuery
{
    public function waiting() {
        return $this->andWhere(['status' => Statuses::STATUS_DISABLED]);
    }

    public function active() {
        return $this->andWhere(['status' => Statuses::STATUS_ACTIVE]);
    }

    public function using() {
        return $this->andWhere('status != :removed', [':removed' => Statuses::STATUS_REMOVED]);
    }

    public function ordering() {
        return $this->orderBy(['ordering' => SORT_ASC, 'id' => SORT_DESC]);
    }

    public function byCamp($camp_id) {
        return $this->andWhere(['base_id' => $camp_id]);
    }
    
    public function byPartner($partner_id) {
        return $this->andWhere(['partner_id' => $partner_id]);
    }
    
    public function byUser($user_id) {
        return $this->andWhere(['user_id' => $user_id]);
    }
    
    public function bySelf() {
        return $this->byUser(Yii::$app->user->id);
    }
}
