<?php

namespace app\modules\ads\interfaces;

use app\modules\ads\models\Message;

interface MessageServiceInterface
{
    public function createMessage(array $data): Message;
    public function findByExternalId(string $externalMessageId): ?Message;
    public function updateMessageStatus(string $externalMessageId, string $status): void;
}
