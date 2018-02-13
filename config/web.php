<?php
$params = require(__DIR__ . '/params.php');
date_default_timezone_set('Europe/Moscow');

$config = [
    'id' => 'camp',
    'name' => 'api.camp-centr',
    'language'=>'ru-RU',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'formatter' => [
            'thousandSeparator' => '',
        ],
        'response' => [
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    //'prettyPrint' => YII_DEBUG, // use "pretty" output in debug mode
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ],
            ],
            'format' => \yii\web\Response::FORMAT_JSON,
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->data !== null && Yii::$app->request->get('suppress_response_code')) {
                    $response->data = [
                        'success' => $response->isSuccessful,
                        'data' => $response->data,
                    ];
                    $response->statusCode = 200;
                }
            },
        ],
        'user' => [
            'identityClass' => 'app\models\Users',
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                'GET <controller>/list' => '<controller>/list',
                'GET <controller>/camp/<id>' => '<controller>/camp',
                'GET <controller>/<id>' => '<controller>/show',
                'POST <controller>/<id>' => '<controller>/create',
                'PUT <controller>/<id>' => '<controller>/update',
                'DELETE <controller>/<id>' => '<controller>/delete',
            ],
        ],
        'log' => [
            'traceLevel' => 3,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error','warning'],
                ],
                [
                    'class' => 'yii\log\EmailTarget',
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'yii\web\HttpException:400',
                        'yii\web\HttpException:403',
                        'yii\web\HttpException:404',
                        'yii\debug\*',
                    ],
                    'message' => [
                        'from' => ['error@camp-centr.ru' => 'Api.CampCentr'],
                        'to' => [$params['adminEmail']],
                        'subject' => 'Site error',
                    ],
                    'logVars' => ['_SERVER', '_POST', '_GET', '_PUT', '_DELETE'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
    ],
    'params' => $params,
];

    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';
    
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = ['class' => 'yii\gii\Module',
                                 'allowedIPs' => ['::1','127.0.0.1','94.19.219.69','78.140.198.50']];

return $config;