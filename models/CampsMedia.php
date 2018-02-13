<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\helpers\Normalize;
use app\models\queries\CampsMediaQuery;
use yii\helpers\Json;

/**
 * This is the model class for table "{{%camps_media}}".
 *
 * @property integer $id
 * @property integer $camp_id
 * @property string $photo_order_free
 * @property string $photo_main
 * @property string $photo_partner
 * @property string $photos_concert_hall
 * @property string $photos_others
 * @property string $photos_sport
 * @property string $photos_eating
 * @property string $photos_comfort
 * @property string $photos_room
 * @property string $photos_med
 * @property string $photos_security
 * @property string $photos_area
 * @property string $videos
 */
class CampsMedia extends ActiveRecord
{
    public $photos_room_f = [];
    public $photos_comfort_f = [];
    public $photos_eating_f = [];
    public $photos_sport_f = [];
    public $photos_area_f = [];
    public $photos_med_f = [];
    public $photos_security_f = [];
    public $photos_concert_hall_f = [];
    public $photos_others_f = [];
    public $videos_f = [];
    
    public static function tableName()
    {
        return '{{%camps_media}}';
    }

    public function rules()
    {
        return [
            // require
            [['photo_main'], 'required', 'message' => 'Загрузите "{attribute}"'],
            
            [['photos_sport_f', 'photos_room_f', 'photos_area_f'], 'required',
             'message' => 'Загрузите фотографии для раздела "{attribute}"'],
            
            // base
            [['camp_id'], 'integer'],

            [['photo_main', 'photo_partner', 'photo_order_free'], 'string', 'max' => 100],
            
            [['photos_concert_hall', 'photos_others', 'photos_sport', 'photos_eating', 'photos_comfort',
              'photos_room', 'photos_med', 'photos_security', 'photos_area'], 'string', 'max' => 500],
            
            [['videos'], 'string', 'max' => 1000],

            // special
            [['photos_concert_hall_f', 'photos_sport_f', 'photos_eating_f', 'photos_comfort_f',
              'photos_room_f', 'photos_med_f', 'photos_security_f', 'photos_area_f',
              'photos_others_f'], 'each', 'rule' => ['string', 'max' => 50]],

            [['videos_f'], 'each', 'rule' => ['string', 'max' => 500]],

            // counts
            [['photos_sport_f', 'photos_room_f', 'photos_area_f'], 'checkCount', 'skipOnEmpty' => false, 'params' => ['min' => 1, 'max' => 3]],
            [['photos_eating_f', 'photos_comfort_f'], 'checkCount', 'skipOnEmpty' => true, 'params' => ['min' => 0, 'max' => 3]],

            [['photos_concert_hall_f', 'photos_med_f',
              'photos_security_f'], 'checkCount', 'skipOnEmpty' => false, 'params' => ['min' => 0, 'max' => 3]],

            [['photos_others_f'], 'checkCount', 'skipOnEmpty' => true, 'params' => ['min' => 0, 'max' => 10]],
            [['videos_f'], 'checkCount', 'skipOnEmpty' => true, 'params' => ['min' => 0, 'max' => 2]],
        ];
    }
    
    public function checkCount($attribute, $params) {
        
        if ($attribute == 'videos_f') {
            $amount_words = ['видеозаписей', 'видеозаписи', 'видеозаписей'];
        } else {
            $amount_words = ['фотографий', 'фотографии', 'фотографий'];
        }
        
        if (count($this->{$attribute}) < $params['min']) {
            $word_count = Normalize::wordAmount($params['min'], $amount_words, true);
            $this->addError($attribute, 'Загрузите не менее ' . $word_count . ' для раздела ' . $this->getAttributeLabel($attribute));
        } elseif (count($this->{$attribute}) > $params['max']) {
            $word_count = Normalize::wordAmount($params['max'], $amount_words, true);
            $this->addError($attribute, 'Вы не можете загрузить более ' . $word_count . ' для раздела ' . $this->getAttributeLabel($attribute));
        }
    }
    
    public function beforeValidate()
    {
        foreach (['photos_comfort', 'photos_room', 'photos_med', 'photos_security', 'photos_area',
                  'photos_eating', 'photos_sport', 'photos_concert_hall', 'photos_others', 'videos'] AS $name) {
            // массивы в строки
            $name_f = "{$name}_f";
    
            if (isset($this->{$name_f}) && is_array($this->{$name_f})) {
                $this->{$name_f} = array_filter($this->{$name_f});
            } else {
                $this->{$name_f} = null;
            }
            
            if ($this->{$name_f}) {
                $this->{$name} = Json::encode($this->{$name_f});
            }
        }
        
        return parent::beforeValidate();
    }
    
    public function afterFind()
    {
        foreach (['photos_comfort', 'photos_room', 'photos_med', 'photos_security', 'photos_area',
                  'photos_eating', 'photos_sport', 'photos_concert_hall', 'photos_others', 'videos'] AS $name) {
            // строки в массивы
            $name_f = "{$name}_f";
            if (!empty($this->{$name})) $this->{$name_f} = Json::decode($this->{$name});
        }
        
        parent::afterFind();
    }
    
    
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'photo_main' => 'Главное фото лагеря',
            'photo_partner' => 'Фото для раздела Партнеры',
            'photo_order_free' => 'Фото для Забронировать бесплатно',
    
            'photos_room' => 'Номер размещения',
            'photos_room_f' => 'Номер размещения',
            
            'photos_comfort' => 'Удобства',
            'photos_comfort_f' => 'Удобства',
            
            'photos_eating' => 'Столовая',
            'photos_eating_f' => 'Столовая',
            
            'photos_sport' => 'Спортплощадки',
            'photos_sport_f' => 'Спортплощадки',
    
            'photos_area' => 'План/территория лагеря',
            'photos_area_f' => 'План/территория лагеря',
            
            'photos_med' => 'Медпункт',
            'photos_med_f' => 'Медпункт',
            
            'photos_security' => 'Охрана',
            'photos_security_f' => 'Охрана',
            
            'photos_concert_hall' => 'Концертный, актовый зал',
            'photos_concert_hall_f' => 'Концертный, актовый зал',
    
            'photos_others' => 'Дополнительные фотографии',
            'photos_others_f' => 'Дополнительные фотографии',
    
            'videos' => 'Видеозаписи',
            'videos_f' => 'Видеозаписи',
        ]);
    }

    public static function find()
    {
        return new CampsMediaQuery(get_called_class());
    }
}
