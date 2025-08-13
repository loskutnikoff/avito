<?php

namespace app\modules\ads\components;

use app\modules\ads\models\DealerClassifier;

interface PlatformInterface
{
    public function handleWebhook(array $webhookData, DealerClassifier $classifier): bool;

    public function sendMessage(int $userId, string $externalChatId, string $message, int $dealerId): bool;

    public function registerWebhook(string $webhookUrl, int $classifierId): bool;

    public function getWebhooks(int $classifierId): ?array;

    public function deleteWebhook(string $webhookUrl, int $classifierId): bool;

    public function isTokenValid(int $dealerId): bool;
}
