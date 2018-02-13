<?php

namespace app\helpers;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Url;

class MHtml {
    
    /**
     * вывод сообщений после действий
     * @param array $params
     * @return string
     */
    public static function alertMsg($params = []) {

        $class = [];

        $session = Yii::$app->session;

        if ($session->hasFlash('error')) {
            $class[] = 'alert alert-danger';
            if (isset($params['class'])) $class[] = $params['class'];
            
            $total_class = implode(' ', $class);
            return Html::tag('div', $session->getFlash('error'), ['class' => $total_class]);
        } elseif ($session->hasFlash('success')) {
            $class[] = 'alert alert-success';
            if (isset($params['class'])) $class[] = $params['class'];
            
            $total_class = implode(' ', $class);
            return Html::tag('div', $session->getFlash('success'), ['class' => $total_class]);
        }
        
        return '';
    }
    
    /**
     * формирование поля для алиаса
     * @param ActiveRecord $model
     * @param $field_from
     * @param $field_to
     * @param bool $readonly
     * @param string $placeholder
     * @return string
     */
    public static function aliasField(ActiveRecord $model, $field_from, $field_to, $readonly = false, $placeholder = '') {

        $classes = [];
        $classes[] = 'form-group';
        $classes[] = 'field-' . Html::getInputId($model, $field_to);
        if ($model->isAttributeRequired($field_to)) $classes[] = 'required';
        if ($model->hasErrors($field_to)) $classes[] = 'has-error';

        $label = Html::activeLabel($model, $field_to, ['class' => 'control-label']);
        $label_content = Html::tag('div', $label, ['class' => 'col-xs-4 text-right']);

        $input = Html::activeTextInput($model, $field_to, ['class' => 'form-control', 'readonly' => $readonly, 'placeholder' => $placeholder]);

        $button = Html::tag('a', 'Получить URL', [
            'href' => Url::to(['/ajax/alias']),
            'class' => 'input-group-addon btn btn-default btn-alias',
            'title' => 'Получить ' . $model->getAttributeLabel($field_to) . ' из поля ' . $model->getAttributeLabel($field_from),
            'data' => ['from' => Html::getInputId($model, $field_from), 'to' => Html::getInputId($model, $field_to)],
        ]);
        $input_group = Html::tag('div', ($input . $button), ['class' => 'input-group']);
        $input_error = Html::tag('div', implode('<br />', $model->getErrors($field_to)), ['class' => 'help-block']);

        $input_content = Html::tag('div', ($input_group . $input_error), ['class' => 'col-xs-7']);

        return Html::tag('div', ($label_content . $input_content), ['class' => implode(' ', $classes)]);
    }
}