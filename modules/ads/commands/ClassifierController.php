<?php

namespace app\modules\ads\commands;

use app\modules\ads\components\AvitoPlatform;
use app\modules\ads\helpers\WebhookLogic;
use app\modules\ads\models\Advert;
use app\modules\ads\models\Chat;
use app\modules\ads\models\DealerClassifier;
use app\modules\ads\helpers\WebhookManager;
use app\modules\ads\services\AdvertService;
use app\modules\ads\services\ChatService;
use app\modules\ads\services\InterestService;
use app\modules\ads\services\MessageService;
use Exception;
use yii\console\Controller;
use yii\helpers\Console;

class ClassifierController extends Controller
{
    public WebhookLogic $webhookLogic;

    public function init()
    {
        parent::init();
        $this->webhookLogic = new WebhookLogic(
            new AdvertService(),
            new ChatService(),
            new MessageService(),
            new InterestService()
        );
    }

    public function actionCheckWebhooks()
    {
        $this->stdout("Проверка и регистрация вебхуков для классификаторов\n", Console::FG_GREEN);

        $classifiers = $this->getActiveClassifiers();
        if ($classifiers === null) {
            return;
        }

        $registered = 0;
        $errors = 0;
        $skipped = 0;

        /** @var DealerClassifier $classifier */
        foreach ($classifiers as $classifier) {
            $this->stdout("Проверяем классификатор ID {$classifier->id} (тип: {$classifier->type})... ", Console::FG_CYAN);

            if (!$this->checkClassifierCredentials($classifier)) {
                $skipped++;
                continue;
            }

            if (empty($classifier->webhook_token)) {
                $this->stdout("Генерируем webhook токен", Console::FG_CYAN);
                if (!WebhookManager::generateWebhookToken($classifier)) {
                    $this->stdout("Ошибка: Не удалось сохранить токен\n", Console::FG_RED);
                    $errors++;
                    continue;
                }
                $this->stdout("Токен сгенерирован, ", Console::FG_GREEN);
            }

            try {
                $platform = $this->createPlatformSafely($classifier);
                if (!$platform) {
                    $errors++;
                    continue;
                }

                if (!$platform->isTokenValid($classifier->dealer_id)) {
                    $this->stdout("Ошибка: Не удалось получить токен доступа\n", Console::FG_RED);
                    $errors++;
                    continue;
                }

                $this->stdout("Токен получен, проверка вебхуки", Console::FG_GREEN);

                try {
                    $webhookUrl = $classifier->getWebhookUrl();
                } catch (Exception $e) {
                    $this->stdout("Ошибка: Не удалось сгенерировать webhook URL - {$e->getMessage()}\n", Console::FG_RED);
                    $errors++;
                    continue;
                }

                if ($this->isWebhookRegistered($classifier, $webhookUrl)) {
                    $this->stdout("Вебхук уже зарегистрирован\n", Console::FG_GREEN);
                } else {
                    $result = WebhookManager::registerWebhook($classifier);
                    if ($result) {
                        $this->stdout("Вебхук зарегистрирован\n", Console::FG_GREEN);
                        $registered++;
                    } else {
                        $this->stdout("Ошибка: Не удалось зарегистрировать вебхук\n", Console::FG_RED);
                        $errors++;
                    }
                }
            } catch (Exception $e) {
                $this->stdout("Ошибка: " . $e->getMessage() . "\n", Console::FG_RED);
                $errors++;
            }
        }

        $this->stdout("\nИтого:\n", Console::FG_BLUE);
        $this->stdout("- Зарегистрировано новых вебхуков: {$registered}\n", Console::FG_GREEN);
        $this->stdout("- Пропущено (нет учетных данных): {$skipped}\n", Console::FG_YELLOW);
        $this->stdout("- Ошибок: {$errors}\n", Console::FG_RED);
        $this->stdout("- Всего проверено: " . count($classifiers) . "\n", Console::FG_BLUE);
    }

    public function actionUnregistered($classifierId)
    {
        $this->stdout("Отписка от вебхуков для классификатора ID {$classifierId}...\n", Console::FG_GREEN);

        $classifier = DealerClassifier::findOne($classifierId);
        if (!$classifier) {
            $this->stdout("Классификатор не найден\n", Console::FG_RED);

            return;
        }

        try {
            $webhookUrl = $classifier->getWebhookUrl();
            $this->stdout("URL для отписки: {$webhookUrl}\n", Console::FG_CYAN);

            $result = WebhookManager::unsubscribeFromWebhookByUrl($classifier, $webhookUrl);

            if ($result) {
                $this->stdout("Успешно отписались от вебхука\n", Console::FG_GREEN);
            } else {
                $this->stdout("Не удалось отписаться от вебхука\n", Console::FG_RED);
            }
        } catch (Exception $e) {
            $this->stdout("Ошибка: {$e->getMessage()}\n", Console::FG_RED);
        }
    }

    public function actionCheckTokens()
    {
        $this->stdout("Проверка токенов доступа для классификаторов\n", Console::FG_GREEN);

        $classifiers = $this->getActiveClassifiers();
        if ($classifiers === null) {
            return;
        }

        $valid = 0;
        $errors = 0;
        $skipped = 0;

        /** @var DealerClassifier $classifier */
        foreach ($classifiers as $classifier) {
            $this->stdout("Проверяем токен для классификатора ID {$classifier->id} (тип: {$classifier->type})... ", Console::FG_CYAN);

            if (!$this->checkClassifierCredentials($classifier)) {
                $skipped++;
                continue;
            }

            try {
                $platform = $this->createPlatformSafely($classifier);
                if (!$platform) {
                    $errors++;
                    continue;
                }

                if ($platform->isTokenValid($classifier->dealer_id)) {
                    $this->stdout("Токен валиден\n", Console::FG_GREEN);
                    $valid++;
                } else {
                    $this->stdout("Ошибка: Не удалось получить токен\n", Console::FG_RED);
                    $errors++;
                }
            } catch (Exception $e) {
                $this->stdout("Ошибка: " . $e->getMessage() . "\n", Console::FG_RED);
                $errors++;
            }
        }

        $this->stdout("\nРезультат проверки токенов:\n", Console::FG_BLUE);
        $this->stdout("- Валидных токенов: {$valid}\n", Console::FG_GREEN);
        $this->stdout("- Пропущено (нет учетных данных): {$skipped}\n", Console::FG_YELLOW);
        $this->stdout("- Ошибок: {$errors}\n", Console::FG_RED);
        $this->stdout("- Всего проверено: " . count($classifiers) . "\n", Console::FG_BLUE);
    }

    public function actionValidateCredentials()
    {
        $this->stdout("Проверка валидности учетных данных классификаторов\n", Console::FG_GREEN);

        $classifiers = $this->getActiveClassifiers();
        if ($classifiers === null) {
            return;
        }

        $valid = 0;
        $invalid = 0;
        $skipped = 0;

        /** @var DealerClassifier $classifier */
        foreach ($classifiers as $classifier) {
            $this->stdout("Проверяем классификатор ID {$classifier->id} (тип: {$classifier->type})... ", Console::FG_CYAN);

            if (!$this->checkClassifierCredentials($classifier)) {
                $skipped++;
                continue;
            }

            try {
                $platform = $this->createPlatformSafely($classifier);
                if (!$platform) {
                    $invalid++;
                    continue;
                }

                if ($platform->isTokenValid($classifier->dealer_id)) {
                    $this->stdout("Учетные данные валидны\n", Console::FG_GREEN);
                    $valid++;
                } else {
                    $this->stdout("Ошибка: Недействительные учетные данные\n", Console::FG_RED);
                    $invalid++;
                }
            } catch (Exception $e) {
                $this->stdout("Ошибка: " . $e->getMessage() . "\n", Console::FG_RED);
                $invalid++;
            }
        }

        $this->stdout("\nРезультат проверки учетных данных:\n", Console::FG_BLUE);
        $this->stdout("- Валидных: {$valid}\n", Console::FG_GREEN);
        $this->stdout("- Недействительных: {$invalid}\n", Console::FG_RED);
        $this->stdout("- Пропущено (нет данных): {$skipped}\n", Console::FG_YELLOW);
        $this->stdout("- Всего проверено: " . count($classifiers) . "\n", Console::FG_BLUE);
    }

    public function actionGenerateWebhookToken($classifierId)
    {
        $this->stdout("Генерация нового webhook токена для классификатора ID {$classifierId}...\n", Console::FG_GREEN);

        /** @var DealerClassifier $classifier */
        $classifier = DealerClassifier::findOne(['id' => $classifierId]);

        if (!$classifier) {
            $this->stdout("Классификатор не найден\n", Console::FG_RED);

            return;
        }

        $oldToken = $classifier->webhook_token;
        if ($oldToken) {
            $this->stdout("Перезапись существующий токен\n", Console::FG_YELLOW);
        }

        $classifier->webhook_token = null;

        if (WebhookManager::generateWebhookToken($classifier)) {
            $this->stdout("Webhook токен успешно сгенерирован\n", Console::FG_GREEN);
            $this->stdout("Новый токен: {$classifier->webhook_token}\n", Console::FG_CYAN);
            try {
                $webhookUrl = $classifier->getWebhookUrl();
                $this->stdout("Webhook URL: {$webhookUrl}\n", Console::FG_CYAN);
            } catch (Exception $e) {
                $this->stdout("Ошибка генерации Webhook URL: {$e->getMessage()}\n", Console::FG_RED);
            }
            if ($oldToken) {
                $this->stdout("Не забудьте перерегистрировать webhook с новым URL\n", Console::FG_YELLOW);
            }
        } else {
            $this->stdout("Ошибка сохранения токена\n", Console::FG_RED);
        }
    }

    public function actionStatus()
    {
        $this->stdout("Статус классификаторов:\n", Console::FG_GREEN);

        $classifiers = DealerClassifier::find()
            ->orderBy(['dealer_id' => SORT_ASC, 'type' => SORT_ASC])
            ->all();

        if (empty($classifiers)) {
            $this->stdout("Классификаторы не найдены\n", Console::FG_YELLOW);

            return;
        }

        /** @var DealerClassifier $classifier */
        foreach ($classifiers as $classifier) {
            $status = $classifier->is_active ? 'active' : 'inactive';
            $statusColor = $classifier->is_active ? Console::FG_GREEN : Console::FG_RED;
            $hasCredentials = !empty($classifier->client_id) && !empty($classifier->client_secret) ? '+' : '-';
            $hasWebhookToken = !empty($classifier->webhook_token) ? '+' : '-';
            $typeName = DealerClassifier::getTypeLabelStatic($classifier->type);

            $this->stdout($status, $statusColor);
            $this->stdout(
                " ID: {$classifier->id} | Дилер: {$classifier->dealer_id} | Тип: {$typeName} | Учетные данные: {$hasCredentials} | Webhook токен: {$hasWebhookToken}\n",
                Console::FG_CYAN
            );
        }
    }

    public function actionSyncMessages()
    {
        /** @var DealerClassifier $classifier */
        $classifierList = DealerClassifier::find()
            ->where(['is_active' => true, 'type' => DealerClassifier::TYPE_AVITO])
            ->all();

        if (!$classifierList) {
            $this->stdout("Классификаторы не найдены\n", Console::FG_RED);
            return;
        }

        $totalChatsProcessed = 0;
        $totalMessagesSynced = 0;
        $errors = 0;

        /** @var DealerClassifier $classifier */
        foreach ($classifierList as $classifier) {
            $this->stdout("Обработка классификатора ID {$classifier->id}...\n", Console::FG_CYAN);
            $platform = $classifier->createPlatform();
            if (!($platform instanceof AvitoPlatform)) {
                $this->stdout("Ошибка: Платформа не является AvitoPlatform\n", Console::FG_RED);
                $errors++;
                continue;
            }
            /** @var Advert $advert */
            foreach ((array)$classifier->adverts as $advert) {
                /** @var Chat $chat */
                foreach ((array)$advert->chats as $chat) {
                    try {
                        $messagesData = $platform->getMessagesData($chat->external_user_id, $chat->external_chat_id, $classifier);
                        if ($messagesData) {
                            $this->webhookLogic->syncMessages($messagesData, $chat->id);
                            $totalMessagesSynced += count($messagesData['messages'] ?? []);
                            $totalChatsProcessed++;
                            $this->stdout("Синхронизировано сообщений для чата ID {$chat->id}: " . count($messagesData['messages'] ?? []) . "\n", Console::FG_GREEN);
                        } else {
                            $this->stdout("Нет данных о сообщений для чата ID {$chat->id}\n", Console::FG_YELLOW);
                        }
                    } catch (Exception $e) {
                        $this->stdout("Ошибка при синхронизации чата ID {$chat->id}: {$e->getMessage()}\n", Console::FG_RED);
                        $errors++;
                    }
                }
            }
            $this->stdout("\nИтого:\n", Console::FG_BLUE);
            $this->stdout("- Обработано чатов: {$totalChatsProcessed}\n", Console::FG_GREEN);
            $this->stdout("- Синхронизировано сообщений: {$totalMessagesSynced}\n", Console::FG_GREEN);
            $this->stdout("- Ошибок: {$errors}\n", Console::FG_RED);
            $this->stdout("- Всего классификаторов: " . count($classifierList) . "\n", Console::FG_BLUE);
        }
    }

    public function actionHelp()
    {
        $this->stdout("Доступные команды:\n", Console::FG_GREEN);
        $this->stdout("  check-webhooks         - Проверка и регистрация вебхуков (автогенерация токенов)\n", Console::FG_CYAN);
        $this->stdout("  check-tokens           - Проверка токенов доступа (рекомендуется каждый час)\n", Console::FG_CYAN);
        $this->stdout("  validate-credentials   - Проверка валидности учетных данных\n", Console::FG_CYAN);
        $this->stdout("  generate-webhook-token - Принудительная генерация нового webhook токена\n", Console::FG_CYAN);
        $this->stdout("  status                 - Показать статус всех классификаторов\n", Console::FG_CYAN);
        $this->stdout("  sync-messages          - Синхронизация сообщений\n", Console::FG_CYAN);
        $this->stdout("  help                   - Показать эту справку\n", Console::FG_CYAN);

        $this->stdout("\nПримеры использования:\n", Console::FG_GREEN);
        $this->stdout("  php yii ads/classifier/check-webhooks\n", Console::FG_YELLOW);
        $this->stdout("  php yii ads/classifier/check-tokens\n", Console::FG_YELLOW);
        $this->stdout("  php yii ads/classifier/validate-credentials\n", Console::FG_YELLOW);
        $this->stdout("  php yii ads/classifier/generate-webhook-token <classifier_id>\n", Console::FG_YELLOW);
        $this->stdout("  php yii ads/classifier/status\n", Console::FG_YELLOW);
        $this->stdout("  php yii ads/classifier/sync-messages\n", Console::FG_YELLOW);
    }

    private function getActiveClassifiers(): ?array
    {
        $classifiers = DealerClassifier::find()
            ->where(['is_active' => true])
            ->all();

        if (empty($classifiers)) {
            $this->stdout("Активных классификаторов не найдено.\n", Console::FG_YELLOW);

            return null;
        }

        $this->stdout("Найдено активных классификаторов: " . count($classifiers) . "\n", Console::FG_BLUE);

        return $classifiers;
    }

    private function checkClassifierCredentials(DealerClassifier $classifier): bool
    {
        if (empty($classifier->client_id) || empty($classifier->client_secret)) {
            $this->stdout("Отсутствуют учетные данные для дилера {$classifier->dealer_id}\n", Console::FG_YELLOW);
            return false;
        }
        return true;
    }

    private function createPlatformSafely(DealerClassifier $classifier): ?object
    {
        $platform = $classifier->createPlatform();
        if (!$platform) {
            $this->stdout("Ошибка: Не удалось создать платформу\n", Console::FG_RED);
            return null;
        }
        return $platform;
    }

    private function isWebhookRegistered(DealerClassifier $classifier, string $webhookUrl): bool
    {
        $webhooks = WebhookManager::getExistingWebhooks($classifier);
        if (!$webhooks) {
            return false;
        }

        foreach ((array)($webhooks['subscriptions'] ?? []) as $webhook) {
            if (isset($webhook['url']) && $webhook['url'] === $webhookUrl) {
                return true;
            }
        }

        return false;
    }
}
