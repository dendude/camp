<?php

namespace app\helpers;

use app\models\Camps;
use app\models\ComfortTypes;
use app\models\TagsTypes;
use Yii;
use yii\helpers\Url;

class CampHelper
{
    // для формирования ссылок
    const TYPE_COUNTRY      = 'country';
    const TYPE_REGION       = 'region';
    const TYPE_CITY         = 'city';
    const TYPE_TRANSFER     = 'transfer';
    const TYPE_TYPE         = 'type';
    const TYPE_SERVICE      = 'service';
    const TYPE_COMPENSATION = 'compensation';
    const TYPE_GROUPS       = 'groups';
    const TYPE_YEARS        = 'years';
    
    // ссылки для хлебных крошек и фильтров
    public static function getCountryCampsUrl(Camps $model)
    {
        return Url::to(['/site/camps', 'type' => self::TYPE_COUNTRY, 'alias' => $model->about->country->alias]);
    }
    
    public static function getRegionCampsUrl(Camps $model)
    {
        return Url::to(['/site/camps', 'type' => self::TYPE_REGION, 'alias' => $model->about->region->alias]);
    }
    
    public static function getCityCampsUrl(Camps $model)
    {
        return Url::to(['/site/camps', 'type' => self::TYPE_CITY, 'alias' => $model->about->city->alias]);
    }
    
    public static function getTypeCompensationUrl()
    {
        return Url::to(['/site/camps', 'type' => self::TYPE_COMPENSATION, 'alias' => 'yes']);
    }
    
    public static function getTypeGroupsUrl()
    {
        return Url::to(['/site/camps', 'type' => self::TYPE_GROUPS, 'alias' => 'yes']);
    }
}