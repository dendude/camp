<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Stats]].
 *
 * @see Stats
 */
class StatsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Stats[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Stats|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
