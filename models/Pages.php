<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\PagesQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%pages}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $title
 * @property string $alias
 * @property string $meta_t
 * @property string $meta_d
 * @property string $meta_k
 * @property string $content
 * @property integer $created
 * @property integer $modified
 * @property string $crumbs_verb
 * @property string $crumbs_real
 * @property integer $status
 * @property integer $is_sitemap
 * @property integer $is_auto
 */
class Pages extends ActiveRecord
{
    const PAGE_INDEX_ID = 1;
    const PAGE_NEWS_ID = 2;
    const PAGE_REVIEWS_ID = 3;
    const PAGE_CAMP_REGISTER_ID = 4;
    const PAGE_ABOUT_ID = 5;
    const PAGE_PARENTS_ID = 6;
    const PAGE_FOR_CAMPS = 7;
    const PAGE_BONUSES_ID = 8;
    const PAGE_CONTACTS_ID = 9;
    const PAGE_HOT_TICKETS_ID = 11;
    const PAGE_GROUPS_ID = 12;
    const PAGE_MOMS_DADS_ID = 13;
    const PAGE_CAMPS_ID = 14;
    
    const PAGE_FREE_TRANS = 34;
    const PAGE_WITH_ESCORT = 35;
    const PAGE_COMPENSATION = 36;
    const PAGE_GROUP_DISCOUNT = 37;
    const PAGE_ORDER = 38;
    const PAGE_REVIEW_ADD = 39;
    const PAGE_CATALOG = 52;
    const PAGE_SELECTIONS = 53;
    
    protected static $reserved_aliases = ['camps', 'login', 'register', 'restore', 'reset'];
    
    public $crumbs_real_arr = [];
    public $crumbs_verb_arr = [];
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%pages}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['title', 'alias', 'manager_id', 'created'], 'required'],

            ['alias', 'unique'],
            
            [['manager_id', 'created', 'modified', 'status'], 'integer'],
            [['manager_id', 'created', 'modified', 'status'], 'default', 'value' => 0],
            
            [['content'], 'required', 'when' => function(self $model){
                return !($model->is_auto);
            }, 'whenClient' => "function(attr, val){
                return ($('#is_auto').prop('checked') === false);
            }"],
            
            [['title', 'alias', 'meta_t', 'meta_d', 'meta_k'], 'string', 'max' => 250],
            [['title', 'alias', 'meta_t', 'meta_d', 'meta_k'], 'default', 'value' => ''],
            
            [['crumbs_verb', 'crumbs_real'], 'string', 'max' => 1000],
            [['crumbs_verb', 'crumbs_real'], 'default', 'value' => ''],
            
            [['is_sitemap', 'is_auto'], 'boolean'],
            [['is_sitemap', 'is_auto'], 'default', 'value' => 0],
            
            [['crumbs_real_arr', 'crumbs_verb_arr'], 'safe'],
        ];
    
        foreach (self::$reserved_aliases AS $alias) {
            $rules[] = ['alias', 'compare', 'compareValue' => $alias, 'operator' => '!=', 'message' => 'Cсылка "{value}" является зарезервированной, введите другую'];
        }
        
        return $rules;
    }
    
    public function isSearchFilter()
    {
        return in_array($this->id, [
            self::PAGE_ABOUT_ID,
            self::PAGE_PARENTS_ID,
            self::PAGE_CONTACTS_ID,
            self::PAGE_BONUSES_ID,
            self::PAGE_FOR_CAMPS,
        ]);
    }
    
    public static function getAliasById($page_id) {
        $page = self::findOne($page_id);
        return $page ? $page->alias : '#';
    }
    
    public static function getUrlById($page_id, $params = []) {
        $page = self::findOne($page_id);
        return $page ? Url::to(array_merge(['/site/page', 'alias' => $page->alias], $params)) : '#';
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        $this->manager_id = Yii::$app->user->id;
    
        $this->crumbs_real = serialize($this->crumbs_real_arr);
        $this->crumbs_verb = serialize($this->crumbs_verb_arr);
        
        return parent::beforeValidate();
    }
    
    public function afterFind()
    {
        $this->crumbs_real_arr = unserialize($this->crumbs_real);
        $this->crumbs_verb_arr = unserialize($this->crumbs_verb);
        
        parent::afterFind();
    }
    
    
    public static function getFilterList() {
        $list = self::find()->orderBy(['title' => SORT_ASC])->all();
        return $list ? ArrayHelper::map($list, 'id', 'title') : [];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'title' => 'Заголовок (H1)',
            'alias' => 'Ссылка на страницу (Alias)',
            'meta_t' => 'Meta-заголовок',
            'meta_d' => 'Meta-описание',
            'meta_k' => 'Meta-ключевики',
            'content' => 'Содержимое',
            'crumbs_real' => 'Хлебные крошки',
            'crumbs_verb' => 'Фальш-крошки',
            'is_sitemap' => 'Отображать в карте сайта',
            'is_auto' => 'Автоконтент или форма',
        ]);
    }
    
    public static function find()
    {
        return new PagesQuery(get_called_class());
    }
}
