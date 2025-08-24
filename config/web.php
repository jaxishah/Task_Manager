<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'ymHuGYFEBk8gXcFA44MnZu54NaahaEKd',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser', // This tells Yii: whenever Content-Type: application/json, parse raw body into PHP array
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
            'class' => 'app\components\ApiErrorHandler',
        ],
        'response' => [
            
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                $request = Yii::$app->request;

                // Detect real API request
                $isApiRequest = (strpos($request->headers->get('Accept'), 'application/json') !== false);
                $isReturnResponse = false;
                if($isApiRequest){
                    $response->format = yii\web\Response::FORMAT_JSON;
                    $message = 'Something Went Wrong!';
                    $error = ['error' => 'Something Went Wrong!'];
                    if( preg_match('#^api(/|$)#', $request->pathInfo)){
                        if ($response->data !== null && $response->statusCode >= 400) {
                            // error like 400 / 401(Unauthorized)    
                            if(isset($response->data['name'])){
                                $isReturnResponse = true;
                                $message = $response->data['name'];
                                $error['error'] =  $response->data['message'];
                            }
                           
                            //if data already set then not need to call this. response already catch via try block
                            
                            if (!(is_array($response->data) || is_object($response->data))) {
                                $isReturnResponse = true;
                            }
                        }
                    }else{
                        // if url wrong like http://localhost:8080/api5555555/task/
                        $isReturnResponse = true;
                        
                    }
                    if($isReturnResponse){
                        $response->data = [
                            'success' => false,
                            'code' => $response->statusCode,
                            'message' => $message,
                            'errors' => $error
                        ];
                    }
                }

            },
        ],
        
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                //['class' => 'yii\rest\UrlRule', 'controller' => ['api/task']],
                 // Explicit API routes
                    'GET api/task' => 'api/task/index',
                    'GET api/task/<id:\d+>' => 'api/task/view',
                    'POST api/task' => 'api/task/create',
                    'PATCH api/task/<id:\d+>' => 'api/task/update',
                    'DELETE api/task/<id:\d+>' => 'api/task/delete',
                    'PATCH api/task/<id:\d+>/restore' => 'api/task/restore',
                    'PATCH api/task/<id:\d+>/toggle-status' => 'api/task/toggle-status',
                    'GET api/tags' => 'api/task/tag-list',
            ],
        ],
     
        'taskService' => [
            'class' => \app\services\TaskService::class,
        ],
    ],
    'params' => $params,
    
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}
$config['params']['bsVersion'] = '5.x';
return $config;
