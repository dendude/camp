<?php

namespace app\models;

use app\helpers\Normalize;
use app\models\queries\CampsQuery;
use app\models\queries\TagsTypesQuery;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%tags_places}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property string $icon
 * @property string $title
 * @property string $title_full
 * @property string $title_many
 * @property string $alias
 * @property string $meta_t
 * @property string $meta_d
 * @property string $meta_k
 * @property string $content
 * @property integer $ordering
 * @property integer $status
 * @property integer $created
 * @property integer $modified
 */
class TagsTypes extends ActiveRecord
{
    const GROUPS_ID = 21;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tags_types}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manager_id', 'created', 'title', 'title_many', 'title_full', 'alias', 'content'], 'required'],
            
            ['alias', 'unique'],
            
            [['manager_id', 'ordering', 'status', 'created', 'modified'], 'integer'],
            [['manager_id', 'ordering', 'status', 'created', 'modified'], 'default', 'value' => 0],
            
            [['icon', 'title', 'title_many', 'title_full', 'alias', 'meta_t', 'meta_d', 'meta_k'], 'string', 'max' => 250],
            [['icon', 'title', 'title_many', 'title_full', 'alias', 'meta_t', 'meta_d', 'meta_k'], 'default', 'value' => ''],
            
            ['content', 'string'],
        ];
    }
    
    public static function getFilterList() {
        $list = self::find()->ordering()->active()->all();
        return $list ? ArrayHelper::map($list, 'id', 'title') : [];
    }
    
    public static function getFilterListWithCamps($country_id = null, $region_id = null) {
        
        /** @var $query CampsQuery */
        $query = Camps::find()->active();
        if ($country_id) $query->byCountry($country_id);
        if ($region_id) $query->byRegion($region_id);
        /** @var $camps Camps[] */
        $camps = $query->all();
        
        $types_ids = [0];
        foreach ($camps AS $camp) {
            $types_ids = array_merge($types_ids, array_values($camp->about->tags_types_f));
        }
        $types_ids = array_unique($types_ids);
        
        $list = self::find()->andWhere(['id' => $types_ids])->active()->ordering()->all();
        
        return $list ? ArrayHelper::map($list, 'id', function(self $model) use ($country_id, $region_id){
            
            $query = Camps::find()->byType($model->id)->active();
            if ($country_id) $query->byCountry($country_id);
            if ($region_id) $query->byRegion($region_id);
            
            return $model->title . ' [' . $query->count() . ']';
        }) : [];
    }
    
    public static function getAliasById($id) {
        $model = self::findOne($id);
        return $model ? $model->alias : null;
    }
    
    public function getUrl()
    {
        return Url::to(['camps', 'type' => Camps::TYPE_TYPE, 'alias' => $this->alias]);
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        $this->manager_id = Yii::$app->user->id;
        if (empty($this->alias)) $this->alias = Normalize::alias($this->title);
        
        return parent::beforeValidate();
    }
    
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'icon' => 'Иконка',
            'title' => 'Короткое название',
            'title_full' => 'Полное название',
            'title_many' => 'Множественное название',
            'content' => 'Содержимое',
        ]);
    }
    
    /**
     * @inheritdoc
     * @return TagsTypesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TagsTypesQuery(get_called_class());
    }
}
