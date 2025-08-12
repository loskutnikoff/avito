<?php

namespace app\modules\ads\models;

use app\components\behaviors\TimestampBehavior;
use app\helpers\ArrayHelper;
use app\models\Source;
use app\modules\ads\models\DealerClassifier;
use app\modules\lms\models\RequestType;
use app\models\User;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $platform_type
 * @property int $source_id
 * @property int $request_type_id
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int $updated_by
 *
 * @property RequestType $requestType
 * @property Source $source
 * @property User $createdBy
 * @property User $updatedBy
 */
class ClassifierRequestType extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'dsf_ads_classifier_request_type';
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

    public function rules(): array
    {
        return [
            [['platform_type', 'request_type_id'], 'required'],
            [['platform_type', 'source_id', 'request_type_id'], 'integer'],
            [['platform_type'], 'in', 'range' => array_keys(self::getPlatformTypeList())],
            [['platform_type'], 'unique', 'message' => 'Настройка для данного типа платформы уже существует'],
            [['request_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => RequestType::class, 'targetAttribute' => ['request_type_id' => 'id']],
            [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => Source::class, 'targetAttribute' => ['source_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'platform_type' => Yii::t('app', 'Тип платформы'),
            'request_type_id' => Yii::t('app', 'Тип цели обращения'),
            'source_id' => Yii::t('app', 'Источник'),
            'created_at' => Yii::t('app', 'Дата создания'),
            'updated_at' => Yii::t('app', 'Дата обновления'),
            'created_by' => Yii::t('app', 'Создал'),
            'updated_by' => Yii::t('app', 'Обновил'),
        ];
    }

    public static function getPlatformTypeList(): array
    {
        return DealerClassifier::getTypeList();
    }

    public function getPlatformTypeName(): ?string
    {
        return self::getPlatformTypeList()[$this->platform_type] ?? null;
    }

    public function getRequestType()
    {
        return $this->hasOne(RequestType::class, ['id' => 'request_type_id']);
    }

    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }

    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public static function getRequestTypeForPlatform(int $platformType): ?int
    {
        $mapping = self::find()
            ->where(['platform_type' => $platformType])
            ->select('request_type_id')
            ->scalar();

        return $mapping ? (int)$mapping : null;
    }

    public static function getSourceForPlatform(int $platformType): ?int
    {
        $mapping = self::find()
            ->where(['platform_type' => $platformType])
            ->select('source_id')
            ->scalar();

        return $mapping ? (int)$mapping : null;
    }

    public static function getList(): array
    {
        return ArrayHelper::map(self::find()->all(), 'id', function ($model) {
            return $model->getPlatformTypeName();
        });
    }
}
