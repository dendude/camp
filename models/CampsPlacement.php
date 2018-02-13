<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\helpers\Normalize;
use app\models\queries\CampsPlacementQuery;

/**
 * This is the model class for table "{{%camps_placement}}".
 *
 * @property integer $id
 * @property integer $camp_id
 * @property string $placement_details
 * @property string $placement_groups
 * @property integer $placement_count_eat
 * @property string $placement_med
 * @property string $placement_security
 * @property string $placement_program
 * @property string $placement_regime_day
 * @property string $placement_regime_tour
 * @property boolean $is_without_places
 */
class CampsPlacement extends ActiveRecord
{
    // удобства
    const PLACEMENT_COMFORT_ROOM = 'room';
    const PLACEMENT_COMFORT_FLOOR = 'floor';
    const PLACEMENT_COMFORT_BLOCK = 'block';
    
    public static function getPlacementTypes() {
        return [
            self::PLACEMENT_COMFORT_ROOM => 'В номере',
            self::PLACEMENT_COMFORT_FLOOR => 'На этаже',
            self::PLACEMENT_COMFORT_BLOCK => 'На блок',
        ];
    }
    public function getPlacementName($type) {
        $list = self::getPlacementTypes();
        return isset($list[$type]) ? $list[$type] : 'not found';
    }
    
    public static function tableName()
    {
        return '{{%camps_placement}}';
    }

    public function rules()
    {
        return [
            [['placement_count_eat', 'placement_details'], 'required'],

            [['camp_id'], 'integer'],
            [['placement_count_eat'], 'integer', 'min' => 1, 'max' => 30],
            
            ['is_without_places', 'boolean'],
            ['is_without_places', 'default', 'value' => 0],
            
            [['placement_details', 'placement_groups', 'placement_med', 'placement_security',
              'placement_program', 'placement_regime_day', 'placement_regime_tour'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'placement_details' => 'Подробное описание',
            'placement_groups' => 'Особенности принятия групп',
            'placement_count_eat' => 'Скольки разовое питание',
            'placement_med' => 'Организация медобслуживания',
            'placement_security' => 'Организация охраны',
            'placement_program' => 'Описание программы лагеря',
            'placement_regime_day' => 'Дневной режим по часам',
            'placement_regime_tour' => 'Режим смены по дням',
            'is_without_places' => 'Без размещения',
        ]);
    }

    public static function find()
    {
        return new CampsPlacementQuery(get_called_class());
    }
}
