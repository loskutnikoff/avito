<?php

namespace app\modules\ads\interfaces;

use app\modules\ads\models\Chat;

interface ChatServiceInterface
{
    public function createChat(array $data): Chat;
    public function findByExternalId(string $externalChatId): ?Chat;
    public function findById(int $id): ?Chat;
    public function updateLastMessageTime(int $chatId): void;
    public function linkToInterest(int $chatId, int $interestId): void;
}
