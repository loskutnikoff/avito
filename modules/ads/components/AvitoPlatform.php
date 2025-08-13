<?php

namespace app\modules\ads\components;

use app\modules\ads\clients\AvitoApiClient;
use app\modules\ads\dto\AvitoWebhookDto;
use app\modules\ads\dto\LeadDataDto;
use app\modules\ads\models\DealerClassifier;
use app\modules\ads\models\Message;
use app\modules\ads\services\TokenManager;
use Exception;
use Yii;

class AvitoPlatform extends BasePlatform
{
    private AvitoApiClient $apiClient;

    public function __construct(TokenManager $tokenManager)
    {
        parent::__construct($tokenManager);
        $this->apiClient = new AvitoApiClient();
    }

    protected function getPlatformType(): int
    {
        return DealerClassifier::TYPE_AVITO;
    }

    protected function getPlatformName(): string
    {
        return 'Avito';
    }

    public function handleWebhook(array $webhookData, DealerClassifier $classifier): bool
    {
        try {
            $dto = new AvitoWebhookDto($webhookData);

            if ($dto->isSystemMessage()) {
                $this->logInfo("Системное сообщение, обработка хука отменена");

                return false;
            }

            if (!$dto->chat_id) {
                $this->logError("Отсутствует chat_id в webhook данных");

                return false;
            }

            $chatData = $this->getChatData((int)$dto->user_id, $dto->chat_id, $classifier);
            $userName = $this->getUserNameFromChatData($chatData, $dto->user_id) ?? (Yii::t('app', 'Продавец') . ' ' . $dto->user_id);
            $authorName = $this->getUserNameFromChatData($chatData, $dto->author_id) ?? (Yii::t('app', 'Покупатель') . ' ' . $dto->author_id);

            $advert = null;
            if ($dto->item_id) {
                $advert = $this->webhookLogic->processAdvert(
                    $dto->item_id,
                    $classifier,
                    $chatData['context']['value']['title'] ?? null
                );
            }

            $chatDataForProcessing = [
                'user_id' => $dto->user_id,
                'author_id' => $dto->author_id,
                'user_name' => $userName,
                'author_name' => $authorName,
            ];
            $chat = $this->webhookLogic->processChat($dto->chat_id, $advert, $chatDataForProcessing);

            if ($dto->isNewMessage() && $dto->message_id) {
                $messageData = [
                    'chat_id' => $chat->id,
                    'external_message_id' => $dto->message_id,
                    'sender_type' => $dto->getSenderType(),
                    'sender_id' => (string)$dto->author_id,
                    'sender_name' => $dto->isUserMessage() ? $authorName : $userName,
                    'message_type' => $dto->message_type,
                    'content' => $dto->text ?? '',
                ];
                $this->webhookLogic->processMessage($messageData);
            }
// сильно нагрузит, вынес в @see ClassifierController
//            $messagesData = $this->getMessagesData((int)$dto->user_id, $dto->chat_id, $classifier);
//            if ($messagesData) {
//                $this->webhookLogic->syncMessages($messagesData, $chat->id);
//            }

            if ($dto->isUserMessage()) {
                $leadData = new LeadDataDto([
                    'external_id' => $dto->chat_id,
                    'dealer_id' => $classifier->dealer_id,
                    'source_type' => DealerClassifier::TYPE_AVITO,
                    'user_id' => $dto->user_id,
                    'user_name' => $userName,
                    'author_id' => $dto->author_id,
                    'author_name' => $authorName,
                    'message' => $dto->text ?? '',
                    'advert_title' => $chatData['context']['value']['title'] ?? null,
                    'item_id' => $dto->item_id,
                    'chat_type' => $dto->chat_type,
                    'chat_id' => $dto->chat_id,

                ]);

                $interestId = $this->webhookLogic->processInterest($chat, $leadData, true);

                if ($interestId) {
                    $this->logInfo("Webhook успешно обработан. Дилер: {$classifier->dealer_id}, Chat ID: {$chat->id}, Interest ID: {$interestId}");
                } else {
                    $this->logWarning("Не удалось создать обращение из webhook для дилера {$classifier->dealer_id}, но чат и сообщение созданы");
                }
            } else {
                $this->logInfo("Обработка сообщения от дилера. Дилер: {$classifier->dealer_id}, Chat ID: {$chat->id}. Interest не создается.");
            }

            return true;
        } catch (Exception $e) {
            $this->logError("Ошибка обработки webhook", $e);

            return false;
        }
    }

    public function sendMessage(int $userId, string $externalChatId, string $message, int $dealerId): bool
    {
        try {
            $token = $this->getTokenForDealer($dealerId);
            if (!$token) {
                return false;
            }

            $result = $this->apiClient->sendMessage($token, $userId, $externalChatId, $message);

            return $result !== null;
        } catch (Exception $e) {
            $this->logError("Ошибка отправки сообщения", $e);

            return false;
        }
    }

    public function registerWebhook(string $webhookUrl, int $classifierId): bool
    {
        try {
            [$classifier, $token] = $this->getClassifierAndToken($classifierId);
            if (!$classifier || !$token) {
                return false;
            }

            return $this->apiClient->subscribeWebhook($token, $webhookUrl);
        } catch (Exception $e) {
            $this->logError("Ошибка регистрации webhook", $e);

            return false;
        }
    }

    public function getWebhooks(int $classifierId): ?array
    {
        try {
            [$classifier, $token] = $this->getClassifierAndToken($classifierId);
            if (!$classifier || !$token) {
                return null;
            }

            return $this->apiClient->getWebhooks($token);
        } catch (Exception $e) {
            $this->logError("Ошибка получения webhook", $e);

            return null;
        }
    }

    public function deleteWebhook(string $webhookUrl, int $classifierId): bool
    {
        try {
            [$classifier, $token] = $this->getClassifierAndToken($classifierId);
            if (!$classifier || !$token) {
                return false;
            }

            return $this->apiClient->unsubscribeWebhook($token, $webhookUrl);
        } catch (Exception $e) {
            $this->logError("Ошибка удаления webhook", $e);

            return false;
        }
    }

    private function getChatData(int $userId, string $chatId, DealerClassifier $classifier): ?array
    {
        try {
            $token = $this->getTokenForDealer($classifier->dealer_id);
            if (!$token) {
                return null;
            }

            return $this->apiClient->getChatData($token, $userId, $chatId);
        } catch (Exception $e) {
            $this->logError("Ошибка при получении данных чата", $e);

            return null;
        }
    }

    public function getMessagesData(int $userId, string $chatId, DealerClassifier $classifier): ?array
    {
        try {
            $token = $this->getTokenForDealer($classifier->dealer_id);
            if (!$token) {
                return null;
            }

            return $this->apiClient->getMessagesData($token, $userId, $chatId);
        } catch (Exception $e) {
            $this->logError("Ошибка при получении данных чата", $e);

            return null;
        }
    }

    private function getUserNameFromChatData(?array $chatData, ?int $userId): ?string
    {
        if (!$chatData || !$userId || !isset($chatData['users'])) {
            return null;
        }

        foreach ($chatData['users'] as $user) {
            if (isset($user['id']) && (int)$user['id'] === $userId) {
                return $user['name'] ?? null;
            }
        }

        return null;
    }
}
