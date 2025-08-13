<?php

namespace app\modules\ads\dto;

use yii\base\BaseObject;

class MessageContentDto extends BaseObject
{
    public string $text;
    public ?string $flow_id;
}