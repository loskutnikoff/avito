<?php

namespace app\modules\ads\helpers;

use app\modules\ads\dto\LeadDataDto;
use app\modules\ads\dto\MessageDto;
use app\modules\ads\models\DealerClassifier;
use app\modules\ads\models\Advert;
use app\modules\ads\models\Chat;
use app\modules\ads\models\Message;
use app\modules\ads\services\AdvertService;
use app\modules\ads\services\ChatService;
use app\modules\ads\services\MessageService;
use app\modules\ads\services\InterestService;
use Exception;
use Yii;

class WebhookLogic
{
    public function __construct(
        private AdvertService $advertService,
        private ChatService $chatService,
        private MessageService $messageService,
        private InterestService $interestService
    ) {
    }

    public function processAdvert(int $itemId, DealerClassifier $classifier, ?string $title): ?Advert
    {
        $advert = $this->advertService->findByExternalIdAndDealer((string)$itemId, $classifier->dealer_id);

        if (!$advert && $itemId) {
            $advert = $this->advertService->createAdvert([
                'classifier_id' => $classifier->id,
                'external_id' => (string)$itemId,
                'title' => $title ?? (Yii::t('app', 'Объявление') . ' ' . $itemId),
            ]);
        }

        return $advert;
    }

    public function processChat(string $chatId, ?Advert $advert, array $chatData): Chat
    {
        $chat = $this->chatService->findByExternalId($chatId);

        if (!$chat) {
            $chat = $this->chatService->createChat([
                'advert_id' => $advert?->id,
                'external_chat_id' => $chatId,
                'external_user_id' => (string)($chatData['user_id'] ?? ''),
                'user_name' => $chatData['user_name'] ?? (Yii::t('app', 'Продавец') . ' ' . $chatData['user_id'] ?? ''),
                'external_author_id' => (string)($chatData['author_id'] ?? ''),
                'author_name' => $chatData['author_name'] ?? (Yii::t('app', 'Покупатель') . ' ' . $chatData['author_id'] ?? ''),
            ]);
        }

        return $chat;
    }

    public function processMessage(array $messageData): Message
    {
        $message = $this->messageService->findByExternalId($messageData['external_message_id']);
        if (!isset($messageData['external_message_id']) || $message) {
            return $message;
        }

        $message = $this->messageService->createMessage($messageData);

        if (isset($messageData['chat_id'])) {
            $this->chatService->updateLastMessageTime($messageData['chat_id']);
        }

        return $message;
    }

    public function processInterest(Chat $chat, LeadDataDto $leadData, bool $shouldCreateInterest): ?int
    {
        if (!$shouldCreateInterest || $chat->interest_id) {
            return $chat->interest_id;
        }

        $interestId = $this->interestService->createInterest($leadData);

        if ($interestId) {
            $this->chatService->linkToInterest($chat->id, $interestId);
        }

        return $interestId;
    }

    public function syncMessages(array $messagesData, int $chatId): void
    {
        try {
            $incomingDtos = [];
            foreach ($messagesData['messages'] as $messageData) {
                if (($messageData['type'] ?? null) !== Message::SENDER_TYPE_SYSTEM) {
                    $incomingDtos[] = new MessageDto($messageData);
                }
            }

            if (empty($incomingDtos)) {
                Yii::info("Нет несистемных сообщений для обработки в чате {$chatId}", 'ads');

                return;
            }

            $existingMessages = Message::find()
                ->select(['external_message_id', 'is_read'])
                ->andWhere(['chat_id' => $chatId])
                ->asArray()
                ->all();

            $existingReadStatuses = array_column($existingMessages, 'is_read', 'external_message_id');

            $newMessagesToInsert = [];
            $updatesToApply = [];

            $chat = Chat::findOne($chatId);
            if (!$chat) {
                Yii::error("Чат с ID {$chatId} не найден для синхронизации сообщений.", 'ads');

                return;
            }

            foreach ($incomingDtos as $dto) {
                if ($chat->external_user_id == $dto->author_id) {
                    $senderName = $chat->user_name;
                } elseif ($chat->external_author_id == $dto->author_id) {
                    $senderName = $chat->author_name;
                } else {
                    $senderName = Yii::t('app', 'Неизвестный отправитель') . ' ' . $dto->author_id;
                }

                $senderType = $dto->direction == Message::TYPE_DIRECTION_IN ? Message::SENDER_TYPE_USER : Message::SENDER_TYPE_DEALER;

                $readAt = $dto->read ? date('Y-m-d H:i:s', $dto->read) : null;
                if (!isset($existingReadStatuses[$dto->id])) {
                    $createdAt = $dto->created ? date('Y-m-d H:i:s', $dto->created) : null;
                    $newMessagesToInsert[] = [
                        'chat_id' => $chatId,
                        'external_message_id' => $dto->id,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                        'content' => $dto->content->text ?? '',
                        'is_read' => $dto->isRead,
                        'read_at' => $readAt,
                        'sender_id' => (string)($dto->author_id ?? ''),
                        'sender_name' => $senderName,
                    ];
                } elseif ((int)$existingReadStatuses[$dto->id] !== (int)$dto->isRead) {
                    $updatesToApply[$dto->id] = [
                        'is_read' => $dto->isRead,
                        'read_at' => $readAt,
                        'sender_type' => $senderType, //todo потом удалить, что то не проставляется
                    ];
                }
            }

            Yii::$app->db->transaction(function () use ($newMessagesToInsert, $updatesToApply, $chatId) {
                if (!empty($newMessagesToInsert)) {
                    $columns = array_keys($newMessagesToInsert[0]);
                    $rows = array_map('array_values', $newMessagesToInsert);
                    Yii::$app->db->createCommand()->batchInsert(Message::tableName(), $columns, $rows)->execute();
                    Yii::info("Вставлено " . count($newMessagesToInsert) . " новых сообщений для чата {$chatId}", 'ads');
                }

                if (!empty($updatesToApply)) {
                    foreach ($updatesToApply as $externalId => $updateData) {
                        Message::updateAll(
                            $updateData,
                            ['chat_id' => $chatId, 'external_message_id' => $externalId]
                        );
                    }
                    Yii::info("Обновлено " . count($updatesToApply) . " сообщений в чате {$chatId}", 'ads');
                }
            });
        } catch (\yii\db\Exception $e) {
            Yii::error("Ошибка базы данных при синхронизации сообщений для чата {$chatId}: " . $e->getMessage(), 'ads');
        } catch (Exception $e) {
            Yii::error("Общая ошибка при синхронизации сообщений для чата {$chatId}: " . $e->getMessage(), 'ads');
        }
    }
}
