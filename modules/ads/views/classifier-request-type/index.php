<?php

use app\components\Perm;
use app\helpers\Icons;
use app\modules\ads\models\ClassifierRequestType;
use app\modules\ads\models\search\ClassifierRequestTypeSearch;
use yii\data\ActiveDataProvider;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View $this
 * @var ActiveDataProvider $dataProvider
 * @var ClassifierRequestTypeSearch $searchModel
 */

$this->title = Yii::t('app', 'Справочник классифайдов');

$this->registerJs('DSF.AdsClassifierRequestType();');
?>
<section class="unityBlock">
    <div class="pageHeader">
        <div class="pageHeader__left">
            <h2 class="pageHeader__title"><?= Html::encode($this->title) ?></h2>
            <div class="section-filters-btn ml-10">
                <?= Html::a(
                    '<span class="svg--icon">' . Icons::icon('bicolors-filter') . '</span>',
                    '#',
                    ['class' => 'btn-success js-filter'],
                ) ?>
                <?php if (Yii::$app->user->can(Perm::ADS_CLASSIFIER_REQUEST_TYPE_CREATE)): ?>
                    <?= Html::a(
                        Yii::t('app', 'Создать'),
                        ['create'],
                        ['class' => 'btn-primary ml-10 js-show-modal'],
                    ) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="pageHeader__right">
        </div>
    </div>
    <?= $this->render('_search', ['model' => $searchModel]); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => null,
        'tableOptions' => ['class' => 'table table__striped'],
        'columns' => [
            'id',
            [
                'attribute' => 'platform_type',
                'value' => function (ClassifierRequestType $model) {
                    return $model->getPlatformTypeName();
                }
            ],
            [
                'attribute' => 'source_id',
                'value' => function (ClassifierRequestType $model) {
                    return $model->source->name ?? null;
                }
            ],
            [
                'attribute' => 'request_type_id',
                'value' => function (ClassifierRequestType $model) {
                    return $model->requestType->name ?? null;
                }
            ],
            'created_at:datetime',
            [
                'attribute' => 'createdBy.fullName',
                'label' => Yii::t('app', 'Создал'),
            ],
            'updated_at:datetime',
            [
                'attribute' => 'updatedBy.fullName',
                'label' => Yii::t('app', 'Обновил'),
            ],
            [
                'class' => ActionColumn::class,
                'contentOptions' => ['class' => 'nowrap text-right'],
                'template' => '{update} {delete}',
                'buttons' => [
                    'update' => static fn(string $url) => Html::a(
                        '<span class="svg--icon" aria-hidden="true">' . Icons::icon('bicolors-edit') . '</span>',
                        $url,
                        [
                            'class' => 'btn-icon mr-10 js-show-modal',
                            'title' => Yii::t('app', 'Редактировать'),
                        ],
                    ),
                    'delete' => static fn(string $url, ClassifierRequestType $model) => Html::a(
                        '<span class="svg--icon" aria-hidden="true">' . Icons::icon('bicolors-delete') . '</span>',
                        $url,
                        [
                            'data-toggle' => 'tooltip',
                            'title' => Yii::t('app', 'Удалить'),
                            'class' => 'btn-icon ml-5 js-confirm',
                            'data' => [
                                'confirm-button' => Yii::t('app', 'Удалить'),
                                'confirm-message' => Yii::t(
                                    'app',
                                    'Вы уверены, что хотите удалить классифайд?',
                                    ['id' => $model->id],
                                ),
                            ],
                        ],
                    ),
                ],
                'visibleButtons' => [
                    'update' => static fn(): bool => Yii::$app->user->can(Perm::ADS_CLASSIFIER_REQUEST_TYPE_UPDATE),
                    'delete' => static fn(): bool => Yii::$app->user->can(Perm::ADS_CLASSIFIER_REQUEST_TYPE_DELETE),
                ],
            ],
        ],
    ]); ?>
</section>