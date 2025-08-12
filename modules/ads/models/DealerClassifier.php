<?php

namespace app\modules\ads\models;

use app\components\behaviors\TimestampBehavior;
use app\models\Dealer;
use app\modules\ads\components\PlatformInterface;
use app\modules\ads\factories\PlatformFactory;
use Exception;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $dealer_id
 * @property int $type
 * @property string $client_id
 * @property string $client_secret
 * @property string|null $webhook_token
 * @property bool $is_active
 * @property string $created_at
 * @property string $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Dealer $dealer
 */
class DealerClassifier extends ActiveRecord
{
    public const TYPE_AVITO = 1;
    public const TYPE_AUTORU = 2;

    public static function tableName(): string
    {
        return 'dsf_ads_dealer_classifier';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
        ];
    }

    public function createPlatform(): ?PlatformInterface
    {
        try {
            return PlatformFactory::create($this);
        } catch (Exception $e) {
            Yii::error("Ошибка создания платформы: " . $e->getMessage(), 'ads');

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['dealer_id', 'type', 'client_id', 'client_secret'], 'required'],
            [['dealer_id', 'type'], 'integer'],
            [['client_id', 'client_secret', 'webhook_token'], 'string', 'max' => 255],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
            [['type'], 'in', 'range' => array_keys(self::getTypeList())],
            [['dealer_id', 'type'], 'unique', 'targetAttribute' => ['dealer_id', 'type']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'dealer_id' => Yii::t('app', 'Дилер'),
            'type' => Yii::t('app', 'Тип платформы'),
            'client_id' => Yii::t('app', 'Client ID'),
            'client_secret' => Yii::t('app', 'Client Secret'),
            'webhook_token' => Yii::t('app', 'Webhook токен'),
            'is_active' => Yii::t('app', 'Активен'),
            'created_at' => Yii::t('app', 'Создан'),
            'updated_at' => Yii::t('app', 'Обновлен'),
            'created_by' => Yii::t('app', 'Создал'),
            'updated_by' => Yii::t('app', 'Обновил'),
        ];
    }

    public function getDealer()
    {
        return $this->hasOne(Dealer::class, ['id' => 'dealer_id']);
    }

    public static function getTypeList(): array
    {
        return [
            self::TYPE_AVITO => Yii::t('app', 'Авито'),
            self::TYPE_AUTORU => Yii::t('app', 'Авто.ру'),
        ];
    }

    public function getTypeLabel(): string
    {
        return self::getTypeLabelStatic($this->type);
    }

    public static function getTypeLabelStatic(int $type): string
    {
        return self::getTypeList()[$type] ?? 'Неизвестно';
    }

    public function getWebhookUrl(): string
    {
        $endpoint = match ((int)$this->type) {
            self::TYPE_AVITO => '/ads/webhook/avito',
            self::TYPE_AUTORU => '/ads/webhook/autoru',
            default => null
        };

        if (!$endpoint) {
            throw new Exception('Не найден endpoint');
        }

        if (Yii::$app->has('urlManager') && Yii::$app->has('request')) {
            try {
                return Yii::$app->urlManager->createAbsoluteUrl([$endpoint,
                    'classifier_id' => $this->id,
                    'token' => $this->webhook_token,
                ]);
            } catch (Exception $e) {
                Yii::error($e->getMessage(), 'ads');
            }
        }

        $baseUrl = Yii::$app->request->hostInfo ?? null;
        if (!$baseUrl) {
            throw new Exception("Не удалось определить домен для генерации webhook URL. Проверьте конфигурацию urlManager или request.");
        }

        return rtrim($baseUrl, '/') . $endpoint . '?classifier_id=' . $this->id . '&token=' . $this->webhook_token;
    }
}