<?php

namespace app\modules\ads\factories;

use app\modules\ads\components\PlatformInterface;
use app\modules\ads\components\AvitoPlatform;
use app\modules\ads\components\AutoruPlatform;
use app\modules\ads\models\DealerClassifier;
use app\modules\ads\services\TokenManager;
use Yii;

class PlatformFactory
{
    private static ?TokenManager $tokenManager = null;

    public static function create(DealerClassifier $classifier): ?PlatformInterface
    {
        $tokenManager = self::getTokenManager();

        return match ((int)$classifier->type) {
            DealerClassifier::TYPE_AVITO => new AvitoPlatform($tokenManager),
            DealerClassifier::TYPE_AUTORU => new AutoruPlatform($tokenManager),
            default => null,
        };
    }

    private static function getTokenManager(): TokenManager
    {
        if (self::$tokenManager === null) {
            self::$tokenManager = new TokenManager(Yii::$app->cache);
        }

        return self::$tokenManager;
    }

    public static function reset(): void
    {
        self::$tokenManager = null;
    }
}
