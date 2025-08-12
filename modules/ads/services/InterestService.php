<?php

namespace app\modules\ads\services;

use app\models\Dealer;
use app\modules\ads\interfaces\InterestInterface;
use app\modules\ads\dto\LeadDataDto;
use app\modules\ads\models\Chat;
use app\modules\ads\models\ClassifierRequestType;
use app\modules\ads\models\DealerClassifier;
use app\modules\lms\models\forms\InterestForm;
use app\modules\lms\models\Interest;
use Exception;
use Yii;
use yii\base\Component;

class InterestService extends Component implements InterestInterface
{
    public function createInterest(LeadDataDto $leadData): ?int
    {
        try {
            Yii::info("Начинаем создание обращения: {$leadData->external_id}", 'ads');

            if ($this->interestExists($leadData->external_id, $leadData->dealer_id)) {
                Yii::warning("Обращение уже существует: {$leadData->external_id}", 'ads');
                return null;
            }

            Yii::info("Обращение не существует, создаем новое", 'ads');

            $interestId = $this->createInterestRecord($leadData);
            if (!$interestId) {
                Yii::error("Не удалось создать обращение: {$leadData->external_id}", 'ads');
                return null;
            }

            Yii::info("Создано обращение: {$interestId}", 'ads');
            return $interestId;

        } catch (Exception $e) {
            Yii::error("Ошибка создания обращения: " . $e->getMessage(), 'ads');
            Yii::error("Stack trace: " . $e->getTraceAsString(), 'ads');
            return null;
        }
    }

    private function createInterestRecord(LeadDataDto $leadData): ?int
    {
        try {
            Yii::info("Создаем запись Interest", 'ads');

            $interest = new InterestForm();
            $interest->scenario = Interest::SCENARIO_ADS;
            $interest->dealer_id = $leadData->dealer_id;
            $interest->comment = $this->formatInterestDescription($leadData);
            $interest->first_name = $leadData->author_name;
            $interest->status = Interest::STATUS_HIDDEN;
            $interest->type = Interest::TYPE_LEAD;
            $interest->source_id = $this->getSourceId($leadData);
            // $interest->channel_id = $this->getChannelId($leadData);
            // $interest->campaign_id = $this->getCampaignId();
            $interest->request_type_id = $this->getRequestTypeId($leadData);
            $interest->distributor_id = $this->getDistributorId($leadData);
            $interest->avito_chat_id = $leadData->chat_id;

            Yii::info("Поля Interest заполнены, сохраняем...", 'ads');

            if (!$interest->save()) {
                Yii::error("Ошибка создания обращения: " . json_encode($interest->errors), 'ads');
                return null;
            }

            Yii::info("Interest сохранен с ID: {$interest->id}", 'ads');

            Yii::info("Обращение #{$interest->id} создано успешно", 'ads');
            return $interest->id;

        } catch (Exception $e) {
            Yii::error("Ошибка в createInterestRecord: " . $e->getMessage(), 'ads');
            Yii::error("Stack trace: " . $e->getTraceAsString(), 'ads');
            return null;
        }
    }

    public function interestExists(string $externalChatId, int $dealerId): bool
    {
        return Chat::find()
            ->joinWith(['advert.classifier'])
            ->where(['dsf_ads_chat.external_chat_id' => $externalChatId])
            ->andWhere(['dsf_ads_dealer_classifier.dealer_id' => $dealerId])
            ->andWhere(['IS NOT', 'dsf_ads_chat.interest_id', null])
            ->exists();
    }

    private function formatInterestDescription(LeadDataDto $leadData): string
    {
        $platformName = DealerClassifier::getTypeLabelStatic($leadData->source_type);
        $description = "Обращение с {$platformName}\n";
        $description .= "external_id: {$leadData->external_id}\n";
        $description .= "Сообщение: {$leadData->message}\n";

        if ($leadData->advert_title) {
            $description .= "Объявление: {$leadData->advert_title}\n";
        }

        if ($leadData->advert_id) {
            $description .= "ID объявления: {$leadData->advert_id}\n";
        }

        if ($leadData->item_id) {
            $description .= "ID объявления на площадке: {$leadData->item_id}\n";
        }

        if ($leadData->chat_type) {
            $description .= "Тип чата: {$leadData->chat_type}\n";
        }

        if ($leadData->chat_id) {
            $description .= "ID чата: {$leadData->chat_id}\n";
        }

        if ($leadData->author_name && $leadData->author_id !== $leadData->user_id) {
            $description .= "Автор сообщения: {$leadData->author_name}\n";
        }

        return $description;
    }

    private function getSourceId(LeadDataDto $leadData): ?int
    {
        $platformType = $this->getPlatformType($leadData->source_type);
        $sourceId = ClassifierRequestType::getSourceForPlatform($platformType);

        if ($sourceId) {
            return $sourceId;
        }

        Yii::warning("Не найден источник для платформы {$leadData->source_type}", 'ads');
        return null;
    }

    private function getChannelId(LeadDataDto $leadData): ?int
    {
        return null;
    }

    private function getCampaignId(): ?int
    {
        return null;
    }

    private function getRequestTypeId(LeadDataDto $leadData): ?int
    {
        $platformType = $this->getPlatformType($leadData->source_type);
        $requestTypeId = ClassifierRequestType::getRequestTypeForPlatform($platformType);

        if ($requestTypeId) {
            return $requestTypeId;
        }

        Yii::warning("Не найден тип запроса для платформы {$leadData->source_type}", 'ads');
        return null;
    }

    private function getDistributorId(LeadDataDto $leadData): ?int
    {
        $dealer = Dealer::findOne($leadData->dealer_id);
        if ($dealer && $dealer->distributor_id) {
            return $dealer->distributor_id;
        }

        Yii::warning("Не найден дистрибьютор", 'ads');
        return null;
    }

    private function getPlatformType(int $sourceType): int
    {
        return match ($sourceType) {
            DealerClassifier::TYPE_AUTORU => DealerClassifier::TYPE_AUTORU,
            default => DealerClassifier::TYPE_AVITO,
        };
    }
}