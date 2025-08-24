<?php

namespace app\components;

use yii\web\Response;

class ApiResponse
{
    public static function success(string $message = 'Success', int $statusCode = 200,$data = []): array
    {
        \Yii::$app->response->statusCode = $statusCode;
        \Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'success' => true,
            'status' => $statusCode,
            'message' => $message,
            'data' => $data
        ];
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): array
    {
        \Yii::$app->response->statusCode = $statusCode;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        
        return [
            'success' => false,
            'status' => $statusCode,
            'message' => $message,
            'errors' => $errors ?: null,
        ];

    }
}
