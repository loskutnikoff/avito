<?php

namespace app\modules\ads\controllers;

use app\actions\CreateAction;
use app\actions\DeleteAction;
use app\actions\IndexAction;
use app\actions\UpdateAction;
use app\components\Perm;
use app\modules\ads\models\ClassifierRequestType;
use app\modules\ads\models\search\ClassifierRequestTypeSearch;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class ClassifierRequestTypeController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => static fn() => Yii::$app->user->loginRequired(),
                'rules' => [
                    ['actions' => ['index', 'list'], 'allow' => true, 'roles' => [Perm::ADS_CLASSIFIER_REQUEST_TYPE_LIST]],
                    ['actions' => ['create'], 'allow' => true, 'roles' => [Perm::ADS_CLASSIFIER_REQUEST_TYPE_CREATE]],
                    ['actions' => ['update'], 'allow' => true, 'roles' => [Perm::ADS_CLASSIFIER_REQUEST_TYPE_UPDATE]],
                    ['actions' => ['delete'], 'allow' => true, 'roles' => [Perm::ADS_CLASSIFIER_REQUEST_TYPE_DELETE]],
                ],
            ],
        ];
    }

    public function actions(): array
    {
        return [
            'index' => [
                'class' => IndexAction::class,
                'modelClass' => ClassifierRequestTypeSearch::class,
            ],
            'create' => [
                'class' => CreateAction::class,
                'modelClass' => ClassifierRequestType::class,
            ],
            'update' => [
                'class' => UpdateAction::class,
                'modelClass' => ClassifierRequestType::class,
            ],
            'delete' => [
                'class' => DeleteAction::class,
                'modelClass' => ClassifierRequestType::class,
            ],
        ];
    }
}