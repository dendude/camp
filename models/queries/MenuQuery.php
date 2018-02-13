<?php

namespace app\models\queries;

use app\helpers\Statuses;
use app\models\Menu;
use Yii;
use yii\db\ActiveQuery;

class MenuQuery extends ActiveQuery
{
    public function active() {
        $this->andWhere(['status' => Statuses::STATUS_ACTIVE]);
        $this->orderBy(['ordering' => SORT_ASC]);
        return $this;
    }

    public function root() {
        $this->andWhere(['parent_id' => 0]);
        $this->orderBy('ordering ASC');
        return $this;
    }

    public function top() {
        $this->andWhere(['parent_id' => Menu::TOP_MENU_ID]);
        return $this;
    }
    
    public function subtop() {
        $this->andWhere(['parent_id' => Menu::TOP_SUBMENU_ID]);
        return $this;
    }
    
    public function bottom() {
        $this->andWhere(['parent_id' => Menu::BOTTOM_MENU_ID]);
        return $this;
    }
}