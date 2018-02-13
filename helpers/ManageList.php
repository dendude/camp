<?php
namespace app\helpers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * получение кнопок действий над записями
 * Class ManageList
 * @package app\helpers
 */
class ManageList {

    /**
     * @param $model - модель
     * @param array $buttons
     * @return string
     */
    public static function get($model, $buttons = ['edit', 'delete']) {

        $actions = [];
        $return = [];

        foreach ($buttons AS $btn_name) {
            $arr_name = explode('-', $btn_name);
            $prt_name = array_pop($arr_name);

            // добавляем кнопки
            switch ($prt_name) {
                case 'login':
                    $actions[$btn_name] = ['icon' => 'share-alt', 'class' => 'default', 'title' => 'Войти пользователем'];
                    break;
                case 'show':
                    $actions[$btn_name] = ['icon' => 'search', 'class' => 'default', 'title' => 'Просмотр', 'target' => '_blank'];
                    break;
                case 'edit':
                    $actions[$btn_name] = ['icon' => 'pencil', 'class' => 'info', 'title' => 'Редактировать'];
                    break;
                case 'cancel':
                    $actions[$btn_name] = ['icon' => 'remove', 'class' => 'danger', 'title' => 'Отменить'];
                    break;
                case 'delete':
                    $actions[$btn_name] = ['icon' => 'trash', 'class' => 'danger', 'title' => 'Удалить'];
                    break;
            }
        }

        foreach ($actions AS $ak => $av) {
            $act_url = Yii::$app->controller->id . '/' . $ak;  // пример: users/edit
            $target = isset($av['target']) ? $av['target'] : '_self';

            $return[] = Html::tag('a', '<i class="glyphicon glyphicon-' . $av['icon'] . '"></i>', [
                'href' => Url::to([$act_url, 'id' => $model->id]),
                'class' => 'btn btn-sm btn-' . $av['class'],
                'title' => $av['title'],
                'target' => $target,
            ]);
        }

        return Html::tag('div', implode('', $return), ['class' => 'btn-group']);
    }
} 