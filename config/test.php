<?php

$params = require __DIR__ . '/params.php';
$db     = require __DIR__ . '/test_db.php';

return [
    'id'       => 'app-tests',
    'basePath' => dirname(__DIR__),
    'language' => 'en-US',

    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    'components' => [
        'db' => $db,

        'mailer' => [
            'class'           => \yii\symfonymailer\Mailer::class,
            'viewPath'        => '@app/mail',
            'useFileTransport'=> true,
            'messageClass'    => \yii\symfonymailer\Message::class,
        ],

        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
        ],

        'urlManager' => [
            'enablePrettyUrl'   => true,
            'showScriptName'    => false,
            'enableStrictParsing'=> false,
            'rules' => [
                // same rules as your API
                'GET api/task'                          => 'api/task/index',
                'GET api/task/<id:\d+>'                 => 'api/task/view',
                'POST api/task'                         => 'api/task/create',
                'PUT api/task/<id:\d+>'                 => 'api/task/update',
                'PATCH api/task/<id:\d+>'               => 'api/task/update',
                'DELETE api/task/<id:\d+>'              => 'api/task/delete',
                'PATCH api/task/<id:\d+>/restore'       => 'api/task/restore',
                'PATCH api/task/<id:\d+>/toggle-status' => 'api/task/toggle-status',
                'GET api/tags'                          => 'api/task/tag-list',
            ],
        ],

        'request' => [
            'cookieValidationKey' => 'test-key',
            'enableCsrfValidation'=> false,
            'parsers' => [
                'application/json' => yii\web\JsonParser::class,
            ],
        ],

        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'on beforeSend' => static function ($event): void {
                $response = $event->sender;
                if (strpos(Yii::$app->request->pathInfo, 'api/') === 0) {
                    $response->format = yii\web\Response::FORMAT_JSON;
                }
            },
        ],

        'user' => [
            'enableSession' => false,
            'loginUrl'      => null,
            'identityClass' => null, // no User table
        ],

        'errorHandler' => [
            'class' => 'yii\web\ErrorHandler',
        ],
    ],

    'params' => array_merge($params, [
        'apiToken' => 'test_api_token', // for Bearer auth in tests
    ]),
];
