<?php

namespace app\modules\ads\services;

use app\modules\ads\interfaces\MessageServiceInterface;
use app\modules\ads\models\Message;
use app\modules\ads\exceptions\MessageCreationException;
use Yii;
use yii\base\Component;

class MessageService extends Component implements MessageServiceInterface
{
    public function createMessage(array $data): Message
    {
        $message = new Message();
        $message->setAttributes($data);

        if (!$message->save()) {
            throw new MessageCreationException($message->errors);
        }

        Yii::info("Создано сообщение: {$message->id}", 'ads');

        return $message;
    }

    public function findByExternalId(string $externalMessageId): ?Message
    {
        return Message::findOne(['external_message_id' => $externalMessageId]);
    }

    public function updateMessageStatus(string $externalMessageId, string $status): void
    {
        $message = $this->findByExternalId($externalMessageId);
        if (!$message) {
            return;
        }

        if ($status === 'read' && !$message->is_read) {
            $message->is_read = true;
            $message->read_at = date('Y-m-d H:i:s');
            $message->save();
        }
    }
}