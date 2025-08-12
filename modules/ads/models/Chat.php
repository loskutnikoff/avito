<?php

namespace app\modules\ads\models;

use app\components\behaviors\TimestampBehavior;
use app\models\Dealer;
use app\modules\lms\models\Interest;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $advert_id
 * @property int|null $interest_id
 * @property string $external_chat_id
 * @property string|null $external_user_id
 * @property string|null $user_name
 * @property string|null $author_name
 * @property string|null $external_author_id
 * @property string $status
 * @property string|null $last_message_at
 * @property string $created_at
 * @property string $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Advert $advert
 * @property Interest|null $interest
 * @property Message[] $messages
 */
class Chat extends ActiveRecord
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public static function tableName(): string
    {
        return 'dsf_ads_chat';
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
            [['external_chat_id'], 'required'],
            [['advert_id', 'interest_id'], 'integer'],
            [['external_chat_id', 'external_user_id', 'user_name', 'author_name', 'external_author_id'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 50],
            [['last_message_at'], 'safe'],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['external_chat_id'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'advert_id' => Yii::t('app', 'Объявление'),
            'interest_id' => Yii::t('app', 'Обращение'),
            'external_chat_id' => Yii::t('app', 'Внешний ID чата'),
            'external_user_id' => Yii::t('app', 'Внешний ID пользователя'),
            'user_name' => Yii::t('app', 'Имя пользователя'),
            'author_name' => Yii::t('app', 'Имя автора/продавца'),
            'external_author_id' => Yii::t('app', 'Внешний ID автора/продавца'),
            'status' => Yii::t('app', 'Статус'),
            'last_message_at' => Yii::t('app', 'Последнее сообщение'),
            'created_at' => Yii::t('app', 'Создан'),
            'updated_at' => Yii::t('app', 'Обновлен'),
            'created_by' => Yii::t('app', 'Создал'),
            'updated_by' => Yii::t('app', 'Обновил'),
        ];
    }

    public function getAdvert()
    {
        return $this->hasOne(Advert::class, ['id' => 'advert_id']);
    }

    public function getInterest()
    {
        return $this->hasOne(Interest::class, ['id' => 'interest_id']);
    }

    public function getMessages()
    {
        return $this->hasMany(Message::class, ['chat_id' => 'id']);
    }

    public function getDealer()
    {
        return $this->hasOne(Dealer::class, ['id' => 'dealer_id'])
            ->via('advert.classifier');
    }

    public static function getStatusList(): array
    {
        return [
            self::STATUS_ACTIVE => Yii::t('app', 'Активный'),
            self::STATUS_CLOSED => Yii::t('app', 'Закрыт'),
            self::STATUS_ARCHIVED => Yii::t('app', 'Архивирован'),
        ];
    }

    public function getStatusLabel(): string
    {
        return self::getStatusList()[$this->status] ?? 'Неизвестно';
    }
}