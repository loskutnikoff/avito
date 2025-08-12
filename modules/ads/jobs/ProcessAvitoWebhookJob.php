<?php

namespace app\modules\ads\jobs;

use app\components\queue\AbstractJob;
use app\modules\ads\models\DealerClassifier;
use Exception;
use Yii;

class ProcessAvitoWebhookJob extends AbstractJob
{
    public array $webhookData = [];
    public ?int $dealerId = null;
    public ?int $classifierId = null;

    public function execute($queue): void
    {
        parent::execute($queue);

        try {
            Yii::info("Обработка вебхука Avito в фоне", 'ads');

            $query = DealerClassifier::find()->where(['type' => DealerClassifier::TYPE_AVITO, 'is_active' => true]);
            if ($this->classifierId) {
                $query->andWhere(['id' => $this->classifierId]);
            }
            if ($this->dealerId) {
                $query->andWhere(['dealer_id' => $this->dealerId]);
            }
            /** @var DealerClassifier $classifier */
            $classifier = $query->one();

            if (!$classifier) {
                Yii::error("Не найден активный классификатор для Avito", 'ads');
                return;
            }

            $platform = $classifier->createPlatform();
            if (!$platform) {
                Yii::error("Не удалось создать платформу для Avito", 'ads');
                return;
            }

            $result = $platform->handleWebhook($this->webhookData, $classifier);

            if ($result) {
                Yii::info("Вебхук Avito успешно обработан для дилера {$classifier->dealer_id}", 'ads');
            } else {
                Yii::error("Ошибка обработки вебхука Avito для дилера {$classifier->dealer_id}", 'ads');
            }

        } catch (Exception $e) {
            Yii::error("Ошибка обработки вебхука Avito в фоне: " . $e->getMessage(), 'ads');
            throw $e;
        }
    }
}