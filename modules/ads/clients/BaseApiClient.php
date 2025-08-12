<?php

namespace app\modules\ads\clients;

use app\modules\ads\models\DealerClassifier;
use yii\httpclient\Client;

abstract class BaseApiClient
{
    protected string $apiUrl;
    protected Client $httpClient;

    public function __construct()
    {
        $this->apiUrl = $this->getApiUrl();
        $this->httpClient = new Client();
    }

    abstract protected function getApiUrl(): string;

    abstract public function getAccessToken(DealerClassifier $classifier): ?string;

    abstract public function subscribeWebhook(string $token, string $webhookUrl): bool;

    abstract public function getWebhooks(string $token): ?array;

    abstract public function unsubscribeWebhook(string $token, string $webhookUrl): bool;

    abstract public function sendMessage(string $token, string $chatId, string $message): ?array;
}
