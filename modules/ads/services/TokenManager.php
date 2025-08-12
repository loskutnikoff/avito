<?php

namespace app\modules\ads\services;

use app\modules\ads\clients\AvitoApiClient;
use app\modules\ads\clients\AutoruApiClient;
use app\modules\ads\clients\BaseApiClient;
use app\modules\ads\models\DealerClassifier;
use Yii;
use yii\caching\CacheInterface;

class TokenManager
{
    private const TOKEN_CACHE_DURATION = 86400; // 24 часа

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getToken(int $dealerId, int $platformType): ?string
    {
        $cacheKey = "ads_token_{$dealerId}_{$platformType}";

        $token = $this->cache->get($cacheKey);
        if ($token) {
            return $token;
        }

        $classifier = $this->getClassifier($dealerId, $platformType);
        if (!$classifier) {
            return null;
        }

        $apiClient = $this->createApiClient($platformType);
        if (!$apiClient) {
            return null;
        }

        $token = $apiClient->getAccessToken($classifier);

        if (!$token) {
            return null;
        }

        $this->cache->set($cacheKey, $token, self::TOKEN_CACHE_DURATION);

        return $token;
    }

    private function getClassifier(int $dealerId, int $platformType): ?DealerClassifier
    {
        return DealerClassifier::find()
            ->where(['dealer_id' => $dealerId, 'type' => $platformType, 'is_active' => true])
            ->one();
    }

    private function createApiClient(int $platformType): ?BaseApiClient
    {
        return match ($platformType) {
            DealerClassifier::TYPE_AVITO => new AvitoApiClient(),
            DealerClassifier::TYPE_AUTORU => new AutoruApiClient(),
            default => null,
        };
    }
}
