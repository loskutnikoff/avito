<?php

namespace app\modules\ads\clients;

use app\modules\ads\models\DealerClassifier;
use Yii;

/**
 * Заглушка API для AutoRu
 */
class AutoruApiClient extends BaseApiClient
{
    public const API_URL = 'https://api.auto.ru';

    protected function getApiUrl(): string
    {
        return self::API_URL;
    }

    public function getAccessToken(DealerClassifier $classifier): ?string
    {
        Yii::warning("Auto.ru API не реализован", 'ads');
        return null;
    }

    public function subscribeWebhook(string $token, string $webhookUrl): bool
    {
        Yii::warning("Auto.ru подписка на вебхуки не реализована", 'ads');
        return false;
    }

    public function getWebhooks(string $token): ?array
    {
        Yii::warning("Auto.ru получение вебхуков не реализовано", 'ads');
        return null;
    }

    public function unsubscribeWebhook(string $token, string $webhookUrl): bool
    {
        Yii::warning("Auto.ru отписка от вебхуков не реализована", 'ads');
        return false;
    }

    public function sendMessage(string $token, int $userId, string $chatId, string $message): ?array
    {
        Yii::warning("Auto.ru отправка сообщений не реализована", 'ads');
        return null;
    }
}
