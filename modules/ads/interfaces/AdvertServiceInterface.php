<?php

namespace app\modules\ads\interfaces;

use app\modules\ads\models\Advert;

interface AdvertServiceInterface
{
    public function createAdvert(array $data): Advert;
    public function findById(int $id): ?Advert;
    public function findByExternalId(string $externalId): ?Advert;
    public function findByExternalIdAndDealer(string $externalId, int $dealerId): ?Advert;
}
