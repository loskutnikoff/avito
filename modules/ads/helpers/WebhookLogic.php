<?php

namespace app\modules\ads\helpers;

use app\modules\ads\dto\LeadDataDto;
use app\modules\ads\models\DealerClassifier;
use app\modules\ads\models\Advert;
use app\modules\ads\models\Chat;
use app\modules\ads\services\AdvertService;
use app\modules\ads\services\ChatService;
use app\modules\ads\services\MessageService;
use app\modules\ads\services\InterestService;
use Yii;

class WebhookLogic
{
    public function __construct(
        private AdvertService $advertService,
        private ChatService $chatService,
        private MessageService $messageService,
        private InterestService $interestService
    ) {}

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

    public function processMessage(array $messageData): void
    {
        if (!isset($messageData['external_message_id']) ||
            $this->messageService->findByExternalId($messageData['external_message_id'])) {
            return;
        }

        $this->messageService->createMessage($messageData);

        if (isset($messageData['chat_id'])) {
            $this->chatService->updateLastMessageTime($messageData['chat_id']);
        }
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
}
