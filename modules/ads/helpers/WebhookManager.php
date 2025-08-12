<?php

namespace app\modules\ads\helpers;

use app\modules\ads\models\DealerClassifier;
use Exception;
use Yii;

class WebhookManager
{
    public static function getExistingWebhooks(DealerClassifier $classifier): ?array
    {
        try {
            $platform = $classifier->createPlatform();
            if (!$platform) {
                Yii::warning("Не удалось создать платформу для классификатора {$classifier->id}", 'ads');
                return null;
            }

            if (!$platform->isTokenValid($classifier->dealer_id)) {
                Yii::warning("Нет токена доступа для классификатора {$classifier->id}", 'ads');
                return null;
            }

            return $platform->getWebhooks($classifier->id);
        } catch (Exception $e) {
            Yii::warning("Ошибка получения вебхуков для классификатора {$classifier->id}: " . $e->getMessage(), 'ads');
            return null;
        }
    }

    public static function unsubscribeFromWebhookByUrl(DealerClassifier $classifier, string $webhookUrl): bool
    {
        try {
            $platform = $classifier->createPlatform();
            if (!$platform) {
                return false;
            }

            $existingWebhooks = self::getExistingWebhooks($classifier);
            if (!$existingWebhooks) {
                return false;
            }

            foreach ((array)($existingWebhooks['subscriptions'] ?? []) as $webhook) {
                if (isset($webhook['url']) && $webhook['url'] === $webhookUrl) {
                    $result = $platform->deleteWebhook($webhook['url'], $classifier->id);
                    if ($result) {
                        Yii::info("Отписка от вебхука {$webhook['url']} для классификатора {$classifier->id}", 'ads');
                    }
                    return $result;
                }
            }

            Yii::info("Вебхук {$webhookUrl} не найден среди подписок классификатора {$classifier->id}", 'ads');
            return true;
        } catch (Exception $e) {
            Yii::warning("Ошибка отписки от вебхука {$webhookUrl} для классификатора {$classifier->id}: " . $e->getMessage(), 'ads');
            return false;
        }
    }

    public static function unsubscribeFromAllWebhooks(DealerClassifier $classifier): void
    {
        try {
            $webhookUrl = $classifier->getWebhookUrl();
            self::unsubscribeFromWebhookByUrl($classifier, $webhookUrl);
        } catch (Exception $e) {
            Yii::error("Ошибка отписки от всех вебхуков классификатора {$classifier->id}: " . $e->getMessage(), 'ads');
        }
    }

    public static function registerWebhook(DealerClassifier $classifier): bool
    {
        try {
            $platform = $classifier->createPlatform();
            if (!$platform) {
                Yii::warning("Не удалось создать платформу для классификатора {$classifier->id}", 'ads');
                return false;
            }

            if (!$platform->isTokenValid($classifier->dealer_id)) {
                Yii::warning("Не удалось получить токен доступа для классификатора {$classifier->id}", 'ads');
                return false;
            }

            $webhookUrl = $classifier->getWebhookUrl();
            $result = $platform->registerWebhook($webhookUrl, $classifier->id);

            if ($result) {
                Yii::info("Успешная подписка на вебхуки для классификатора {$classifier->id}. URL: {$webhookUrl}", 'ads');
            } else {
                Yii::warning("Не удалось подписаться на вебхуки для классификатора {$classifier->id}", 'ads');
            }

            return $result;
        } catch (Exception $e) {
            Yii::error("Ошибка регистрации вебхука для классификатора {$classifier->id}: " . $e->getMessage(), 'ads');
            return false;
        }
    }

    public static function generateWebhookToken(DealerClassifier $classifier): bool
    {
        if (empty($classifier->webhook_token)) {
            $classifier->webhook_token = bin2hex(random_bytes(32));
            $result = $classifier->save();
            if ($result) {
                Yii::info("Сгенерирован webhook токен для классификатора {$classifier->id}", 'ads');
            }
            return $result;
        }
        return true;
    }
}
