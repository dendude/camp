<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[UsersActions]].
 *
 * @see UsersActions
 */
class UsersActionsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return UsersActions[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UsersActions|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
