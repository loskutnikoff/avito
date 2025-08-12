<?php

namespace app\modules\ads\dto;

use app\modules\ads\models\DealerClassifier;
use yii\base\BaseObject;

class LeadDataDto extends BaseObject
{
    public int $dealer_id;
    public string $source = 'ads';
    public int $source_type = DealerClassifier::TYPE_AVITO;
    public string $external_id;
    public string $user_name;
    public string $message;
    public string $chat_id;
    public ?string $advert_title = null;
    public ?string $advert_id = null;
    public ?int $item_id = null;
    public ?int $user_id = null;
    public ?int $author_id = null;
    public ?string $chat_type = null;
    public ?string $author_name = null;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function toArray(): array
    {
        return [
            'dealer_id' => $this->dealer_id,
            'source' => $this->source,
            'source_type' => $this->source_type,
            'external_id' => $this->external_id,
            'user_name' => $this->user_name,
            'message' => $this->message,
            'advert_title' => $this->advert_title,
            'advert_id' => $this->advert_id,
            'item_id' => $this->item_id,
            'user_id' => $this->user_id,
            'author_id' => $this->author_id,
            'chat_type' => $this->chat_type,
            'author_name' => $this->author_name,
            'chat_id' => $this->chat_id,
        ];
    }
}