<?php

namespace app\controllers;

use app\models\Camps;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\QueryParamAuth;
use yii\rest\Controller;

class CampsController extends Controller
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
        /** @var $models Camps[] */
        $models = Camps::find()
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
        /** @var $model Camps */
        if (!$model) {
            $model = Camps::find()->byPartner(Yii::$app->user->id)->using()->andWhere(['id' => $id])->one();
        }
        
        if (!$model) return [];
        
        return [
            'id'        => $model->id,
            'url'       => $model->getCampUrl(),
            'name'      => $model->about->name_short,
            'name_org'  => $model->about->name_org,
            'name_full' => $model->about->name_full,
            'created'   => $model->created,
            'modified'  => $model->modified,
        ];
    }
    
    /**
     * @todo доработка API
     * @param $id
     */
    public function actionCreate($id)
    {
    
    }
    
    /**
     * @todo доработка API
     * @param $id
     */
    public function actionModify($id)
    {
    
    }
    
    /**
     * @todo доработка API
     * @param $id
     */
    public function actionDelete($id)
    {
    
    }
}
