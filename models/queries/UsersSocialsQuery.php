<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[UsersSocials]].
 *
 * @see UsersSocials
 */
class UsersSocialsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return UsersSocials[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UsersSocials|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
