<?php

namespace app\components;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UnauthorizedHttpException;

class SimpleBearerAuth extends HttpBearerAuth
{
    /**
     * 
     *  Authenticate dummy token
     */
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');
        if ($authHeader && preg_match('/^Bearer\\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];

            if ($token === Yii::$app->params['adminToken']) {
                return true; // success
            }
        }

        throw new UnauthorizedHttpException('Invalid or missing API token.');
    }
}
