<?php
namespace app\controllers;

use Yii;
use app\models\BaseItems;
use app\models\Orders;
use yii\filters\AccessControl;
use yii\filters\auth\QueryParamAuth;
use yii\rest\Controller;

class CampItemsController extends Controller
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
    
    
    public function actionList() {
        /** @var $models BaseItems[] */
        $models = BaseItems::find()
                ->byPartner(Yii::$app->user->id)
                ->using()
                ->orderBy('camp_id ASC, date_from ASC, date_to ASC')
                ->all();
            
        if (!$models) return [];
    
        $result = [];
        foreach ($models AS $model) {
            $result[$model->id] = $this->actionShow($model->id, $model);
        }
        
        return $result;
    }
    
    public function actionCamp($id) {
        /** @var $models BaseItems[] */
        $models = BaseItems::find()
                ->byPartner(Yii::$app->user->id)
                ->byCamp($id)
                ->using()
                ->orderBy('camp_id ASC, date_from ASC, date_to ASC')->all();
        
        if (!$models) return [];
    
        $result = [];
        foreach ($models AS $model) {
            $result[$model->id] = $this->actionShow($model->id, $model);
        }
        
        return $result;
    }
    
    public function actionShow($id, $model = null) {
        /** @var $model BaseItems */
        if (!$model) {
            $model = BaseItems::find()->byPartner(Yii::$app->user->id)->using()->andWhere(['id' => $id])->one();
        }
    
        if (!$model) return [];
        
        return [
            'id' => $model->id,
            'camp_id' => $model->camp_id,
            'name_short' => $model->name_short,
            'name_full' => $model->name_full,
            'date_from' => $model->date_from,
            'date_to' => $model->date_to,
            'amount' => [
                'total' => $model->partner_amount,
                'ordered' => Orders::find()->byItem($model->id)->using()->count(),
            ],
            'price_data' => [
                'partner' => [
                    'price' => $model->partner_price,
                    'currency' => $model->currency,
                ],
                'service' => [
                    'price' => $model->getCurrentPrice(),
                    'currency' => $model->getCurrentCurrency(),
                ],
            ],
        ];
    }
    
    public function actionCreate($id) {
    
    }
    
    public function actionModify($id) {
    
    }
    
    public function actionDelete($id) {
    
    }
}
