<?php

namespace app\modules\ads\commands;

use app\modules\ads\models\Chat;
use app\modules\ads\models\DealerClassifier;
use Exception;
use yii\console\Controller;
use yii\helpers\Console;

class TestingController extends Controller
{
    public function actionTestAvitoWebhook($dealerId, $classifierId)
    {
        $this->stdout("Тестирование создания обращения из вебхука Авито\n", Console::FG_GREEN);

        /** @var DealerClassifier $classifier */
        $classifier = DealerClassifier::find()
            ->where(['id' => $classifierId, 'dealer_id' => $dealerId, 'type' => DealerClassifier::TYPE_AVITO])
            ->one();

        if (!$classifier) {
            $this->stdout("Классификатор не найден\n", Console::FG_RED);

            return;
        }

        $this->stdout("Найден классификатор: {$classifier->id} (тип: {$classifier->type})\n", Console::FG_CYAN);

        $timestamp = time();
        $testChatId = 'u2i-test_' . $timestamp;
        $testMessageId = 'msg_test_' . $timestamp;
        $testItemId = 7463793992 + $timestamp;
        $testUserId = 161605962;
        $testAuthorId = 350687742;

        $webhookData = [
            'id' => 'test-webhook-' . $timestamp,
            'version' => 'v3.0.0',
            'timestamp' => $timestamp,
            'payload' => [
                'type' => 'message',
                'value' => [
                    'id' => $testMessageId,
                    'chat_id' => $testChatId,
                    'user_id' => $testUserId,
                    'author_id' => $testAuthorId,
                    'created' => $timestamp,
                    'type' => 'text',
                    'chat_type' => 'u2i',
                    'content' => [
                        'text' => 'Здравствуйте! Интересует автомобиль',
                    ],
                    'item_id' => $testItemId,
                    'published_at' => date('c'),
                ],
            ],
        ];

        $this->stdout("Тестовые данные вебхука созданы\n", Console::FG_CYAN);

        try {
            $platform = $classifier->createPlatform();
            if (!$platform) {
                $this->stdout("Не удалось создать платформу\n", Console::FG_RED);
                return;
            }

            $this->stdout("Платформа создана, обрабатываем вебхук...\n", Console::FG_CYAN);

            $result = $platform->handleWebhook($webhookData, $classifier);

            if ($result) {
                $this->stdout("Обращение успешно создано\n", Console::FG_GREEN);
            } else {
                $this->stdout("Не удалось создать обращение\n", Console::FG_RED);
            }
        } catch (Exception $e) {
            $this->stdout("Ошибка: " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }

    public function actionTestAvitoToken($dealerId, $classifierId)
    {
        $this->stdout("Тестирование получения токена Авито\n", Console::FG_GREEN);

        /** @var DealerClassifier $classifier */
        $classifier = DealerClassifier::find()
            ->where(['id' => $classifierId, 'dealer_id' => $dealerId, 'type' => DealerClassifier::TYPE_AVITO])
            ->one();

        if (!$classifier) {
            $this->stdout("Классификатор не найден\n", Console::FG_RED);

            return;
        }

        $this->stdout("Найден классификатор: {$classifier->id} (тип: {$classifier->type})\n", Console::FG_CYAN);

        try {
            $platform = $classifier->createPlatform();
            if (!$platform) {
                $this->stdout("Не удалось создать платформу\n", Console::FG_RED);

                return;
            }

            $tokenValid = $platform->isTokenValid($classifier->dealer_id);
            if ($tokenValid) {
                $this->stdout("Токен успешно получен и валиден\n", Console::FG_GREEN);
            } else {
                $this->stdout("Не удалось получить токен\n", Console::FG_RED);
            }
        } catch (\Exception $e) {
            $this->stdout("Ошибка: " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }

    public function actionInfo($classifierId)
    {
        $this->stdout("Информация о классификаторе ID {$classifierId}:\n", Console::FG_GREEN);

        /** @var DealerClassifier $classifier */
        $classifier = DealerClassifier::find()
            ->where(['id' => $classifierId])
            ->one();

        if (!$classifier) {
            $this->stdout("Классификатор не найден\n", Console::FG_RED);

            return;
        }

        $this->stdout("- ID: {$classifier->id}\n", Console::FG_CYAN);
        $this->stdout("- Дилер ID: {$classifier->dealer_id}\n", Console::FG_CYAN);
        $this->stdout("- Тип: {$classifier->type} (" . $classifier->getTypeLabel() . ")\n", Console::FG_CYAN);
        $this->stdout("- Client ID: {$classifier->client_id}\n", Console::FG_CYAN);
        $this->stdout("- Активен: " . ($classifier->is_active ? 'Да' : 'Нет') . "\n", Console::FG_CYAN);
        $this->stdout("- Webhook токен: " . ($classifier->webhook_token ? 'Установлен' : 'Не установлен') . "\n", Console::FG_CYAN);
        try {
            $webhookUrl = $classifier->getWebhookUrl();
            $this->stdout("- Webhook URL: {$webhookUrl}\n", Console::FG_CYAN);
        } catch (Exception $e) {
            $this->stdout("- Webhook URL: ОШИБКА - {$e->getMessage()}\n", Console::FG_RED);
        }
    }

    public function actionSendMessage($dealerId, $classifierId, $externalChatId, $message)
    {
        $this->stdout("Тестирование отправки сообщения в чат Авито\n", Console::FG_GREEN);

        /** @var DealerClassifier $classifier */
        $classifier = DealerClassifier::find()
            ->where(['id' => $classifierId, 'dealer_id' => $dealerId, 'type' => DealerClassifier::TYPE_AVITO])
            ->one();

        if (!$classifier) {
            $this->stdout("Классификатор не найден\n", Console::FG_RED);
            return;
        }

        $this->stdout("Найден классификатор: {$classifier->id} (тип: {$classifier->type})\n", Console::FG_CYAN);

        /** @var Chat $chat */
        $chat = Chat::find()->andWhere(['external_chat_id' => $externalChatId])->one();
        if (!$chat) {
            $this->stdout("Не найден чат с данным индентификатором\n", Console::FG_RED);
            return;
        }

        try {
            $platform = $classifier->createPlatform();
            if (!$platform) {
                $this->stdout("Не удалось создать платформу\n", Console::FG_RED);
                return;
            }

            $this->stdout("Платформа создана\n", Console::FG_CYAN);

            $result = $platform->sendMessage($chat->external_user_id, $externalChatId, $message, $dealerId);

            if ($result) {
                $this->stdout("Сообщение успешно отправлено\n", Console::FG_GREEN);
            } else {
                $this->stdout("Не удалось отправить сообщение\n", Console::FG_RED);
            }
        } catch (Exception $e) {
            $this->stdout("Ошибка: " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }

    public function actionHelp()
    {
        $this->stdout("Доступные команды:\n", Console::FG_GREEN);
        $this->stdout("  test-avito-webhook   - Тестирование отправка данных\n", Console::FG_CYAN);
        $this->stdout("  test-avito-token     - Тестирование получения токена Авито\n", Console::FG_CYAN);
        $this->stdout("  info                 - Показать информацию о классификаторе\n", Console::FG_CYAN);
        $this->stdout("  send-message         - Тестирование отправка данных\n", Console::FG_CYAN);
        $this->stdout("  help                 - Показать эту справку\n", Console::FG_CYAN);

        $this->stdout("\nПримеры использования:\n", Console::FG_GREEN);
        $this->stdout("  php yii ads/testing/test-avito-webhook <dealer_id> <classifier_id>\n", Console::FG_YELLOW);
        $this->stdout("  php yii ads/testing/test-avito-token <dealer_id> <classifier_id>\n", Console::FG_YELLOW);
        $this->stdout("  php yii ads/testing/info <classifier_id>\n", Console::FG_YELLOW);
        $this->stdout("  php yii ads/testing/send-message <dealer_id> <classifier_id> <external_chat_id> <message>\n", Console::FG_YELLOW);
        $this->stdout("  php yii ads/testing/help\n", Console::FG_YELLOW);
    }
}
