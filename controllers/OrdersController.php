<?php

namespace app\controllers;

use app\models\Orders;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\QueryParamAuth;
use yii\helpers\Json;
use yii\rest\Controller;

class OrdersController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['class'] = QueryParamAuth::className();
        $behaviors['authenticator']['tokenParam'] = 'hash';
        
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                ],
            ],
        ];
        
        return $behaviors;
    }
    
    public function actionList()
    {
        /** @var $models Orders[] */
        $models = Orders::find()
                        ->byPartner(Yii::$app->user->id)
                        ->using()
                        ->orderBy(['id' => SORT_ASC])
                        ->all();
        
        if (!$models) return [];
        
        $result = [];
        foreach ($models AS $model) {
            $result[$model->id] = $this->actionShow($model->id, $model);
        }
        
        return $result;
    }
    
    public function actionShow($id, $model = null)
    {
        /** @var $model Orders */
        if (!$model) {
            $model = Orders::find()->byPartner(Yii::$app->user->id)->using()->andWhere(['id' => $id])->one();
        }
        
        if (!$model) return [];
        
        return [
            'id'          => $model->id,
            'camp_id'     => $model->camp_id,
            'item_id'     => $model->item_id,
            'price_data'  => [
                'partner' => [
                    'price'    => $model->campItem->partner_price,
                    'currency' => $model->campItem->currency,
                ],
                'service' => [
                    'price'          => $model->campItem->getCurrentPrice(),
                    'currency'       => $model->campItem->getCurrentCurrency(),
                    'discount_type'  => $model->campItem->discount_type,
                    'discount_value' => $model->campItem->discount_value,
                ],
                'client'  => [
                    'price'    => $model->price_user,
                    'currency' => $model->currency,
                ],
            ],
            'client_data' => Json::decode($model->details),
        ];
    }
    
    public function actionCreate($id)
    {
    
    }
    
    public function actionModify($id)
    {
    
    }
    
    public function actionDelete($id)
    {
    
    }
}
