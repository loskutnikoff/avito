<?php

namespace app\modules\ads\services;

use app\modules\ads\interfaces\AdvertServiceInterface;
use app\modules\ads\models\Advert;
use app\modules\ads\exceptions\AdvertCreationException;
use Yii;
use yii\base\Component;

class AdvertService extends Component implements AdvertServiceInterface
{
    public function createAdvert(array $data): Advert
    {
        if (empty($data['classifier_id']) || empty($data['external_id']) || empty($data['title'])) {
            throw new AdvertCreationException([], 'Обязательные поля не заполнены');
        }

        $advert = new Advert();
        $advert->setAttributes($data);

        if (!$advert->save()) {
            throw new AdvertCreationException($advert->errors);
        }

        Yii::info("Создано объявление: {$advert->id}", 'ads');
        return $advert;
    }

    public function findById(int $id): ?Advert
    {
        return Advert::findOne($id);
    }

    public function findByExternalId(string $externalId): ?Advert
    {
        return Advert::findOne(['external_id' => $externalId]);
    }

    public function findByExternalIdAndDealer(string $externalId, int $dealerId): ?Advert
    {
        return Advert::find()
            ->alias('a')
            ->joinWith(['classifier c'])
            ->andWhere(['a.external_id' => $externalId])
            ->andWhere(['c.dealer_id' => $dealerId])
            ->one();
    }
}