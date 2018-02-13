<?php
namespace app\models;

use app\helpers\Normalize;
use app\helpers\Statuses;
use app\models\queries\MenuQuery;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%menu}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $parent_id
 * @property integer $page_id
 * @property string $name
 * @property string $title
 * @property integer $ordering
 * @property integer $status
 * @property integer $created
 * @property integer $modified
 *
 * @property Menu $parent
 * @property Pages $page
 * @property TagsTypes $type
 */
class Menu extends ActiveRecord
{
    const TOP_MENU_ID = 1;
    const BOTTOM_MENU_ID = 2;
    const TOP_SUBMENU_ID = 16;
    
    public static function tableName()
    {
        return '{{%menu}}';
    }

    public function rules()
    {
        return [
            [['manager_id', 'name', 'created'], 'required'],

            [['manager_id', 'parent_id', 'page_id', 'type_id', 'ordering', 'status', 'created', 'modified'], 'integer'],
            [['manager_id', 'parent_id', 'page_id', 'type_id', 'ordering', 'status', 'created', 'modified'], 'default', 'value' => 0],

            [['name', 'title'], 'string', 'max' => 100],
            [['name', 'title'], 'default', 'value' => ''],
        ];
    }

    public function getPage() {
        return $this->hasOne(Pages::className(), ['id' => 'page_id']);
    }
    
    public function getType() {
        return $this->hasOne(TagsTypes::className(), ['id' => 'type_id']);
    }

    public function getParent() {
        return $this->hasOne(Menu::className(), ['id' => 'parent_id']);
    }

    public function getChilds() {
        return $this->hasMany(Menu::className(), ['parent_id' => 'id'])->orderBy(['ordering' => SORT_ASC]);
    }

    public function getChildsactive() {
        return $this->hasMany(Menu::className(), ['parent_id' => 'id'])->andWhere(['status' => Statuses::STATUS_ACTIVE])->orderBy(['ordering' => SORT_ASC]);
    }

    public function beforeValidate()
    {
        $this->manager_id = Yii::$app->user->id;
        if ($this->isNewRecord) {
            $this->created = time();
            // выбираем максимальное значение порядка
            $max_ord = (int) self::find()->where(['parent_id' => $this->parent_id])->max('ordering');
            $this->ordering = ($max_ord + 1);
        } else {
            $this->modified = time();
        }

        $this->manager_id = Yii::$app->user->id;

        return parent::beforeValidate();
    }

    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            // выбираем максимальное значение порядка
            $max_ord = (int) self::find()->where(['parent_id' => $this->parent_id])->max('ordering');
            $this->ordering = ($max_ord + 1);
        }

        return parent::beforeSave($insert);
    }

    public static function getFilterList($only_parents = false) {
        $list = [];

        foreach (self::find()->root()->orderBy('ordering ASC')->all() AS $menu_item) {
            $list[$menu_item->id] = $menu_item->name;

            $childs = self::find()->where(['parent_id' => $menu_item->id])->orderBy('ordering ASC')->all();
            if ($childs) {
                foreach ($childs AS $child_item) {
                    $list[$child_item->id] = '&nbsp;&nbsp;&nbsp;' . $child_item->name;

                    if ($only_parents) continue;

                    $subchilds = self::find()->where(['parent_id' => $child_item->id])->orderBy('ordering ASC')->all();
                    if ($subchilds) {
                        foreach ($subchilds AS $subchild_item) {
                            $list[$subchild_item->id] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $subchild_item->name;
                        }
                    }
                }
            }
        }

        return $list;
    }

    public function attributeLabels() {
        return Normalize::withCommonLabels([
            'name' => 'Название',
            'title' => 'Подсказка',
            'parent_id' => 'Родительский пункт меню',
            'page_id' => 'Прикрепленная страница',
            'type_id' => 'Прикрепленный тип лагеря',
        ]);
    }
    
    public static function find() {
        return new MenuQuery(get_called_class());
    }
}
