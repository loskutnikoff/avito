<?php

namespace app\modules\ads\dto;

use InvalidArgumentException;
use yii\base\BaseObject;
use app\modules\ads\validators\WebhookValidator;

/**
 * @see https://developers.avito.ru/api-catalog/messenger/documentation
 */
class AvitoWebhookDto extends BaseObject
{
    public string $id;
    public string $version;
    public int $timestamp;

    public string $type;

    public ?string $message_id = null;
    public ?string $chat_id = null;
    public ?int $user_id = null;
    public ?int $author_id = null;
    public ?int $created = null;
    public ?string $message_type = null;
    public ?string $chat_type = null;
    public ?string $text = null;
    public ?int $item_id = null;
    public ?string $published_at = null;
    public ?array $content = null;

    public function __construct(array $config = [])
    {
        $data = $config;
        $safeConfig = [
            'id' => $data['id'] ?? '',
            'version' => $data['version'] ?? '',
            'timestamp' => $data['timestamp'] ?? 0,
        ];

        parent::__construct($safeConfig);

        $validation = WebhookValidator::validateAvitoPayload($data);
        if (!$validation->isValid()) {
            throw new InvalidArgumentException('Некорректные webhook данные: ' . $validation->getErrorMessage());
        }

        $this->id = $data['id'] ?? '';
        $this->timestamp = $data['timestamp'] ?? 0;

        if (isset($data['payload'])) {
            $payload = $data['payload'];
            $this->type = $payload['type'] ?? '';

            if (isset($payload['value'])) {
                $this->extractMessageData($payload['value']);
            }
        }
    }

    private function extractMessageData(array $value): void
    {
        $this->message_id = $value['id'] ?? null;
        $this->chat_id = $value['chat_id'] ?? null;
        $this->user_id = $value['user_id'] ?? null;
        $this->author_id = $value['author_id'] ?? null;
        $this->created = $value['created'] ?? null;
        $this->message_type = $value['type'] ?? null;
        $this->chat_type = $value['chat_type'] ?? null;
        $this->item_id = $value['item_id'] ?? null;
        $this->published_at = $value['published_at'] ?? null;

        if (isset($value['content'])) {
            $this->content = $value['content'];
            $this->text = $value['content']['text'] ?? null;
        }
    }

    public function isNewChat(): bool
    {
        return $this->type === 'chat';
    }

    public function isNewMessage(): bool
    {
        return $this->type === 'message';
    }

    public function isMessageStatus(): bool
    {
        return $this->type === 'message_status';
    }

    public function isChatStatus(): bool
    {
        return $this->type === 'chat_status';
    }

    public function isUserMessage(): bool
    {
        // Если author_id == user_id, то это сообщение от создателя объявления (дилера)
        // Если author_id != user_id, то это сообщение от покупателя (потенциального лида)
        return $this->author_id !== $this->user_id;
    }

    public function isDealerMessage(): bool
    {
        // Если author_id == user_id, то это сообщение от дилера (продавца)
        return $this->author_id === $this->user_id;
    }
}