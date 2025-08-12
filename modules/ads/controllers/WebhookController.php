<?php

namespace app\modules\ads\controllers;

use app\modules\ads\jobs\ProcessAvitoWebhookJob;
use app\modules\ads\models\DealerClassifier;
use Exception;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class WebhookController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionAvito()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $payload = Yii::$app->request->getRawBody();

            $data = json_decode($payload, true);
            if (!$data) {
                Yii::error('Неверный JSON в вебхуке от Авито', 'ads');
                Yii::$app->response->statusCode = 400;
                return ['error' => 'Invalid JSON'];
            }

            $classifierId = (int)Yii::$app->request->get('classifier_id', 0) ?: null;
            $token = Yii::$app->request->get('token');

            if (!$classifierId || !$token) {
                Yii::$app->response->statusCode = 400;
                return ['error' => 'classifier_id и token обязательны'];
            }

            /** @var DealerClassifier $classifier */
            $classifier = DealerClassifier::find()
                ->where(['id' => $classifierId, 'is_active' => true])
                ->one();

            if (!$classifier || $classifier->webhook_token !== $token) {
                Yii::$app->response->statusCode = 403;
                return ['error' => 'Неверный classifier_id или token'];
            }

            $job = new ProcessAvitoWebhookJob([
                'webhookData' => $data,
                'classifierId' => $classifierId,
            ]);

            Yii::$app->queue->push($job);
            Yii::info('Вебхук Avito добавлен в очередь для обработки', 'ads');

            return ['success' => true];

        } catch (Exception $e) {
            Yii::error('Ошибка обработки вебхука Авито: ' . $e->getMessage(), 'ads');
            return ['error' => 'Internal server error'];
        }
    }
}