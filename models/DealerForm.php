<?php

namespace app\models;

//some code
use app\modules\ads\models\DealerClassifier;
use app\modules\ads\helpers\WebhookManager;

/**
 * @property DealerClassifier[] $dealerClassifiers
 */
class DealerForm extends Dealer
{
    //some code
    public $classifiers;
    //some code

    public function rules(): array
    {
        return array_merge(
            parent::rules(),
            [
                //some code
                [
                    [
                        'classifiers',
                    ],
                    'safe',
                ],
            ]
        );
    }

    //some methods

    public function save($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate()) {
            return false;
        }
        try {
            Yii::$app->db->transaction(function () {
                //some code
                $classifiersData = $this->classifiers;
                if (is_string($classifiersData)) {
                    $classifiersData = json_decode($classifiersData, true) ?: [];
                } elseif (!is_array($classifiersData)) {
                    $classifiersData = [];
                }
                $classifiers = array_filter($classifiersData ?? []);
                if ($classifiers) {
                    $classifierIds = [];
                    foreach ($classifiersData as $data) {
                        $classifier = !empty($data['id']) ? DealerClassifier::findOne($data['id']) : null;
                        $isNewRecord = !$classifier;
                        $needsTokenRefresh = false;

                        if (!$classifier) {
                            $classifier = new DealerClassifier();
                            $classifier->dealer_id = $this->id;
                            $needsTokenRefresh = true;
                        } else {
                            if (
                                $classifier->type != $data['type']
                                || $classifier->client_id != $data['client_id']
                                || $classifier->client_secret != $data['client_secret']
                                || !$classifier->webhook_token
                            ) {
                                $needsTokenRefresh = true;
                            }
                        }

                        $classifier->type = $data['type'];
                        $classifier->client_id = $data['client_id'];
                        $classifier->client_secret = $data['client_secret'];
                        $oldIsActive = $classifier->is_active;
                        $classifier->is_active = $data['is_active'] ?? true;

                        if ($classifier->save()) {
                            if ($oldIsActive && !$classifier->is_active) {
                                $this->unsubscribeFromWebhooks($classifier);
                                $classifier->webhook_token = null;
                                $classifier->save();
                                Yii::info("Классификатор {$classifier->id} деактивирован, отписка от вебхуков", 'ads');
                            }
                            elseif ($classifier->is_active && $needsTokenRefresh) {
                                $this->processWebhookSubscription($classifier, !$isNewRecord);
                            }
                        }

                        $classifierIds[] = $classifier->id;
                    }
                    $classifiersToDelete = DealerClassifier::find()
                        ->andWhere(['dealer_id' => $this->id])
                        ->andWhere(['not in', 'id', $classifierIds])
                        ->all();

                    foreach ($classifiersToDelete as $classifierToDelete) {
                        $this->unsubscribeFromWebhooks($classifierToDelete);
                        $classifierToDelete->delete();
                    }
                } else {
                    $classifiersToDelete = DealerClassifier::find()
                        ->andWhere(['dealer_id' => $this->id])
                        ->all();

                    foreach ($classifiersToDelete as $classifierToDelete) {
                        $this->unsubscribeFromWebhooks($classifierToDelete);
                        $classifierToDelete->delete();
                    }
                }
                //some code
            });

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    private function processWebhookSubscription(DealerClassifier $classifier, bool $isUpdate = false): void
    {
        try {
            if ($isUpdate && $classifier->webhook_token) {
                try {
                    $webhookUrl = $classifier->getWebhookUrl();
                    WebhookManager::unsubscribeFromWebhookByUrl($classifier, $webhookUrl);
                } catch (Exception $e) {
                    Yii::warning("Ошибка отписки от вебхука: " . $e->getMessage(), 'ads');
                }
            }

            if (!WebhookManager::generateWebhookToken($classifier)) {
                Yii::error("Не удалось сгенерировать webhook токен для классификатора {$classifier->id}", 'ads');
                return;
            }

            WebhookManager::registerWebhook($classifier);

        } catch (Exception $e) {
            Yii::error("Ошибка обработки подписки на вебхуки для классификатора {$classifier->id}: " . $e->getMessage(), 'ads');
        }
    }

    private function unsubscribeFromWebhooks(DealerClassifier $classifier): void
    {
        try {
            $webhookUrl = $classifier->getWebhookUrl();
            WebhookManager::unsubscribeFromWebhookByUrl($classifier, $webhookUrl);
        } catch (Exception $e) {
            Yii::warning("Ошибка отписки от вебхука при удалении классификатора {$classifier->id}: " . $e->getMessage(), 'ads');
        }
    }

    public function afterFind(): void
    {
        parent::afterFind();
        //some code
        $this->classifiers = array_map(
            static fn(DealerClassifier $model) => [
                'id' => $model->id,
                'type' => $model->type,
                'client_id' => $model->client_id,
                'client_secret' => $model->client_secret,
                'is_active' => $model->is_active,
                'webhook_token' => $model->webhook_token,
            ],
            $this->dealerClassifiers
        );
    }
    //some methods
}
