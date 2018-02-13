<?php
namespace app\helpers;

use Yii;

class RedirectHelper {
	public static function go($url) {
        Yii::$app->response->redirect($url)->send();
        Yii::$app->end();
    }
}