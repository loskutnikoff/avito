<?php

namespace app\modules\ads\components;

use app\modules\ads\clients\AutoruApiClient;
use app\modules\ads\models\DealerClassifier;
use app\modules\ads\services\TokenManager;

/**
 * Заглушка платформы для работы с Auto.ru
 */
class AutoruPlatform extends BasePlatform
{
    private AutoruApiClient $apiClient;

    public function __construct(TokenManager $tokenManager)
    {
        parent::__construct($tokenManager);
        $this->apiClient = new AutoruApiClient();
    }

    protected function getPlatformType(): int
    {
        return DealerClassifier::TYPE_AUTORU;
    }

    protected function getPlatformName(): string
    {
        return 'Auto.ru';
    }

    public function handleWebhook(array $webhookData, DealerClassifier $classifier): bool
    {
        return false;
    }

    public function sendMessage(string $chatId, string $message, int $dealerId): bool
    {
        return false;
    }

    public function registerWebhook(string $webhookUrl, int $classifierId): bool
    {
        return false;
    }

    public function getWebhooks(int $classifierId): ?array
    {
        return [];
    }

    public function deleteWebhook(string $webhookUrl, int $classifierId): bool
    {
        return false;
    }
}
