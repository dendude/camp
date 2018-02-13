<?php
namespace app\models;

use app\helpers\Normalize;
use app\models\queries\BasePlacementsQuery;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%base_placements}}".
 *
 * @property integer $id
 * @property integer $camp_id
 * @property integer $comfort_type
 * @property string $comfort_about
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 */
class BasePlacements extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%base_placements}}';
    }

    public function rules()
    {
        return [
            [['comfort_type', 'comfort_about', 'created'], 'required'],
            
            ['comfort_type', 'in', 'range' => array_keys(CampsPlacement::getPlacementTypes())],
            
            [['camp_id', 'created', 'modified', 'status'], 'integer'],
            [['camp_id', 'created', 'modified', 'status'], 'default', 'value' => 0],
            
            [['comfort_about'], 'string', 'max' => 100],
        ];
    }
    
    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        return parent::beforeValidate();
    }
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'camp_id' => 'Лагерь',
            'comfort_type' => 'Тип размещения',
            'comfort_about' => 'Скольки местное размещение',
        ]);
    }

    public static function find()
    {
        return new BasePlacementsQuery(get_called_class());
    }
}
