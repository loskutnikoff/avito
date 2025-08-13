<?php

namespace app\modules\ads\dto;

use yii\base\BaseObject;

class MessageDto extends BaseObject
{
    public string $id;
    public ?int $author_id;
    public int $created;
    public MessageContentDto $content;
    public string $type;
    public string $direction;
    public bool $isRead;
    public ?int $read = null;

    public function __construct($config = [])
    {
        if (isset($config['content']) && is_array($config['content'])) {
            $config['content'] = new MessageContentDto($config['content']);
        }
        parent::__construct($config);
    }
}