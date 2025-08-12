<?php

namespace app\modules\ads\services;

use app\modules\ads\interfaces\ChatServiceInterface;
use app\modules\ads\models\Chat;
use app\modules\ads\exceptions\ChatCreationException;
use Yii;
use yii\base\Component;
use yii\db\Expression;

class ChatService extends Component implements ChatServiceInterface
{
    public function createChat(array $data): Chat
    {
        $chat = new Chat();
        $chat->setAttributes($data);

        if (!$chat->save()) {
            throw new ChatCreationException($chat->errors);
        }

        Yii::info("Создан чат: {$chat->id}", 'ads');
        return $chat;
    }

    public function findByExternalId(string $externalChatId): ?Chat
    {
        return Chat::findOne(['external_chat_id' => $externalChatId]);
    }

    public function findById(int $id): ?Chat
    {
        return Chat::findOne($id);
    }

    public function updateLastMessageTime(int $chatId): void
    {
        Chat::updateAll(
            ['last_message_at' => new Expression('NOW()')],
            ['id' => $chatId]
        );
    }

    public function linkToInterest(int $chatId, int $interestId): void
    {
        Chat::updateAll(
            ['interest_id' => $interestId],
            ['id' => $chatId]
        );
    }
}