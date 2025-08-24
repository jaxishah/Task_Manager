<?php
namespace app\components;

use Yii;
use yii\web\ErrorHandler;

class ApiErrorHandler extends ErrorHandler
{
    protected function renderException($exception)
    {
        // Log every exception centrally
        Yii::error([
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ], __METHOD__);

        // if we want to centralize response of execption
        // Return JSON response for API
        // if (Yii::$app->request->isAjax || Yii::$app->request->isPost) {
        //     Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        //     Yii::$app->response->data = [
        //         'success' => false,
        //         'status' => $exception->statusCode ?? 500,
        //         'message' => $exception->getMessage() ?: 'Internal Server Error',
        //     ];
        //     return;
        // }

        parent::renderException($exception);
    }
}
