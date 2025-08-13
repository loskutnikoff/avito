<?php

namespace app\modules\ads\models;

use app\components\behaviors\TimestampBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $chat_id
 * @property string $external_message_id
 * @property string $sender_type
 * @property string|null $sender_id
 * @property string|null $sender_name
 * @property string $message_type
 * @property string $content
 * @property bool $is_read
 * @property string|null $read_at
 * @property string $created_at
 * @property string $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Chat $chat
 */
class Message extends ActiveRecord
{
    public const SENDER_TYPE_USER = 'user';
    public const SENDER_TYPE_DEALER = 'dealer';
    public const SENDER_TYPE_SYSTEM = 'system';

    public const MESSAGE_TYPE_TEXT = 'text';
    public const MESSAGE_TYPE_IMAGE = 'image';
    public const MESSAGE_TYPE_FILE = 'file';

    public const TYPE_DIRECTION_IN = 'in';
    public const TYPE_DIRECTION_OUT = 'out';

    public static function tableName(): string
    {
        return 'dsf_ads_message';
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
            [['chat_id', 'external_message_id', 'sender_type', 'message_type', 'content'], 'required'],
            [['chat_id'], 'integer'],
            [['external_message_id', 'sender_id', 'sender_name'], 'string', 'max' => 255],
            [['sender_type'], 'string', 'max' => 50],
            [['message_type'], 'string', 'max' => 50],
            [['content'], 'string'],
            [['is_read'], 'boolean'],
            [['read_at'], 'safe'],
            [['is_read'], 'default', 'value' => false],
            [['message_type'], 'default', 'value' => self::MESSAGE_TYPE_TEXT],
            [['external_message_id'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'chat_id' => Yii::t('app', 'Чат'),
            'external_message_id' => Yii::t('app', 'Внешний ID сообщения'),
            'sender_type' => Yii::t('app', 'Тип отправителя'),
            'sender_id' => Yii::t('app', 'ID отправителя'),
            'sender_name' => Yii::t('app', 'Имя отправителя'),
            'message_type' => Yii::t('app', 'Тип сообщения'),
            'content' => Yii::t('app', 'Содержание'),
            'is_read' => Yii::t('app', 'Прочитано'),
            'read_at' => Yii::t('app', 'Время прочтения'),
            'created_at' => Yii::t('app', 'Создано'),
            'updated_at' => Yii::t('app', 'Обновлено'),
            'created_by' => Yii::t('app', 'Создал'),
            'updated_by' => Yii::t('app', 'Обновил'),
        ];
    }

    public function getChat()
    {
        return $this->hasOne(Chat::class, ['id' => 'chat_id']);
    }

    public static function getSenderTypeList(): array
    {
        return [
            self::SENDER_TYPE_USER => Yii::t('app', 'Пользователь'),
            self::SENDER_TYPE_DEALER => Yii::t('app', 'Дилер'),
            self::SENDER_TYPE_SYSTEM => Yii::t('app', 'Система'),
        ];
    }

    public function getSenderTypeLabel(): string
    {
        return self::getSenderTypeList()[$this->sender_type] ?? Yii::t('app', 'Неизвестно');
    }

    public static function getMessageTypeList(): array
    {
        return [
            self::MESSAGE_TYPE_TEXT => Yii::t('app', 'Текст'),
            self::MESSAGE_TYPE_IMAGE => Yii::t('app', 'Изображение'),
            self::MESSAGE_TYPE_FILE => Yii::t('app', 'Файл'),
        ];
    }

    public function getMessageTypeLabel(): string
    {
        return self::getMessageTypeList()[$this->message_type] ?? Yii::t('app', 'Неизвестно');
    }
}