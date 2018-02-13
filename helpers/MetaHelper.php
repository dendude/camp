<?php

namespace app\helpers;

class MetaHelper {
	public static function setMeta($model, &$view) {
        if (isset($model->meta_t)) {
            $view->params['meta_t'] = $model->meta_t;
        } elseif (isset($model->title)) {
            $view->params['meta_t'] = $model->title;
        }
        
        if (isset($model->meta_d)) $view->params['meta_d'] = $model->meta_d;
        if (isset($model->meta_k)) $view->params['meta_k'] = $model->meta_k;
    }
}