<?php

namespace api\controllers;

use common\models\Users;
use common\models\UsersQuery;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\ContentNegotiator;
use yii\filters\RateLimiter;
use yii\filters\VerbFilter;
use yii\rest\ActiveController;
use yii\web\JsonResponseFormatter;
use yii\web\Response;

class AuthController extends ActiveController
{
    public $modelClass = 'common\models\Users';

    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'verbFilter' => [
                'class' => VerbFilter::className(),
                'actions' => $this->verbs(),
            ],
            'rateLimiter' => [
                'class' => RateLimiter::className(),
            ],
        ];
    }

    public function actionLogin()
    {
        $response = \Yii::$app->response;
        $response->statusCode = 200;
        $response->format = Response::FORMAT_JSON;

        $userQuery = new UsersQuery(new Users());
        $user = $userQuery->where(['email' => \Yii::$app->request->post('email')])->one();

        if(empty($user)){
            $response->statusCode = 422;
            $response->data = [
                'errors' => [
                    'Usuário' => 'Usuário não encontrado'
                ]
            ];

            return $response;
        }

        if(!$user->validatePassword(\Yii::$app->request->post('password'))){
            $response->statusCode = 422;
            $response->data = [
                'errors' => [
                    'Senha' => 'Senha inválida para o usuário'
                ]
            ];

            return $response;
        }

        return $response->data = [
            'token' => $user->getAuthKey(),
            'user' => $user
        ];
    }

    public function actionSignup()
    {
        $user = new Users();
        $user->username = \Yii::$app->request->post('email');
        $user->email = \Yii::$app->request->post('email');
        $user->setPassword(\Yii::$app->request->post('password'));
        $user->generateAuthKey();

        $userQuery = new UsersQuery($user);

        $response = \Yii::$app->response;
        $response->statusCode = 200;
        $response->format = Response::FORMAT_JSON;

        if ($userQuery->where(['email' => $user->email])->exists()) {

            $response->statusCode = 422;
            $response->data = [
                'errors' => [
                    'Banco de Dados' => 'Já existe um usuário com esse email'
                ]
            ];

            return $response;
        }

        if (!$user->save()) {
            $response->statusCode = 422;
            $response->data = [
                'errors' => [
                    $user->getErrors()
                ]
            ];
            return $response;
        }

        $response->data = [
            'token' => $user->getAuthKey(),
            'user' => $user
        ];

        return $response;
    }

    public function actionSocialLogin()
    {
        $user = new Users();
        $user->username = \Yii::$app->request->post('user_id');
        $user->email = \Yii::$app->request->post('user_id');
        $user->auth_key = \Yii::$app->request->post('token');
        $user->setPassword(\Yii::$app->request->post('user_id'));

        $userQuery = new UsersQuery($user);

        $response = \Yii::$app->response;
        $response->statusCode = 200;
        $response->format = Response::FORMAT_JSON;

        if ($userQuery->where(['email' => $user->email])->exists()) {
            $user = $userQuery->where(['email' => $user->email])->one();
            $response->data = [
                'token' => $user->getAuthKey(),
                'user' => $user
            ];

            return $response;
        }

        if (!$user->save()) {
            $response->statusCode = 422;
            $response->data = [
                'errors' => [
                    $user->getErrors()
                ]
            ];
            return $response;
        }

        $response->data = [
            'token' => $user->getAuthKey(),
            'user' => $user
        ];

        return $response;
    }

}
