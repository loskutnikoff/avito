<?php

namespace app\modules\ads\components;

use app\modules\ads\helpers\WebhookLogic;
use app\modules\ads\models\DealerClassifier;
use app\modules\ads\services\AdvertService;
use app\modules\ads\services\ChatService;
use app\modules\ads\services\InterestService;
use app\modules\ads\services\MessageService;
use app\modules\ads\services\TokenManager;
use Yii;

abstract class BasePlatform implements PlatformInterface
{
    protected TokenManager $tokenManager;
    protected WebhookLogic $webhookLogic;
    protected InterestService $interestService;
    protected AdvertService $advertService;
    protected ChatService $chatService;
    protected MessageService $messageService;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
        $this->initializeServices();
        $this->webhookLogic = new WebhookLogic(
            $this->advertService,
            $this->chatService,
            $this->messageService,
            $this->interestService
        );
    }

    private function initializeServices(): void
    {
        $this->interestService = new InterestService();
        $this->advertService = new AdvertService();
        $this->chatService = new ChatService();
        $this->messageService = new MessageService();
    }

    abstract protected function getPlatformType(): int;

    protected function getToken(int $dealerId): ?string
    {
        return $this->tokenManager->getToken($dealerId, $this->getPlatformType());
    }

    public function isTokenValid(int $dealerId): bool
    {
        return $this->getToken($dealerId) !== null;
    }

    protected function getClassifierById(int $classifierId): ?DealerClassifier
    {
        $classifier = DealerClassifier::findOne($classifierId);
        if (!$classifier) {
            $this->logError("Классификатор {$classifierId} не найден");
            return null;
        }
        return $classifier;
    }

    protected function getTokenForDealer(int $dealerId): ?string
    {
        $token = $this->getToken($dealerId);
        if (!$token) {
            $this->logError("Не удалось получить токен для дилера {$dealerId}");
            return null;
        }
        return $token;
    }

    protected function getClassifierAndToken(int $classifierId): array
    {
        $classifier = $this->getClassifierById($classifierId);
        if (!$classifier) {
            return [null, null];
        }

        $token = $this->getTokenForDealer($classifier->dealer_id);
        if (!$token) {
            return [null, null];
        }

        return [$classifier, $token];
    }

    protected function logError(string $message, \Throwable $e = null): void
    {
        $platformName = $this->getPlatformName();
        $errorMessage = "[{$platformName}] {$message}";

        if ($e) {
            $errorMessage .= ": " . $e->getMessage();
        }

        Yii::error($errorMessage, 'ads');
    }

    protected function logInfo(string $message): void
    {
        $platformName = $this->getPlatformName();
        Yii::info("[{$platformName}] {$message}", 'ads');
    }

    protected function logWarning(string $message): void
    {
        $platformName = $this->getPlatformName();
        Yii::warning("[{$platformName}] {$message}", 'ads');
    }

    abstract protected function getPlatformName(): string;
}
