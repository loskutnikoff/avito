<?php

namespace app\modules\ads\models;

use app\components\behaviors\TimestampBehavior;
use app\models\Dealer;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $classifier_id
 * @property string $external_id
 * @property string $title
 * @property string $created_at
 * @property string $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Dealer $dealer
 * @property DealerClassifier $classifier
 * @property Chat[] $chats
 */
class Advert extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'dsf_ads_advert';
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
            [['classifier_id', 'external_id', 'title'], 'required'],
            [['classifier_id'], 'integer'],
            [['external_id', 'title'], 'string', 'max' => 255],
            [['classifier_id', 'external_id'], 'unique', 'targetAttribute' => ['classifier_id', 'external_id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'classifier_id' => Yii::t('app', 'Классификатор'),
            'external_id' => Yii::t('app', 'Внешний ID'),
            'title' => Yii::t('app', 'Заголовок'),
            'created_at' => Yii::t('app', 'Создано'),
            'updated_at' => Yii::t('app', 'Обновлено'),
            'created_by' => Yii::t('app', 'Создал'),
            'updated_by' => Yii::t('app', 'Обновил'),
        ];
    }

    public function getDealer()
    {
        return $this->hasOne(Dealer::class, ['id' => 'dealer_id'])
            ->via('classifier');
    }

    public function getClassifier()
    {
        return $this->hasOne(DealerClassifier::class, ['id' => 'classifier_id']);
    }

    public function getChats()
    {
        return $this->hasMany(Chat::class, ['advert_id' => 'id']);
    }
}