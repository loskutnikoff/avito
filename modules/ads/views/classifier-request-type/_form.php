<?php

use app\models\Source;
use app\modules\ads\models\ClassifierRequestType;
use app\widgets\ComboTree;
use app\widgets\Select;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\web\View;
use app\modules\lms\models\RequestType;

/**
 * @var View $this
 * @var ClassifierRequestType $model
 */

$title = $model->isNewRecord
    ? Yii::t('app', 'Новый классифайд')
    : Yii::t('app', 'Редактирование классифайда');

?>
<?php Modal::begin([
    'header' => '<h4 class="modal-title">' . Html::encode($title) . '</h4>',
    'footer' =>
        Html::button(Yii::t('app', 'Закрыть'), ['class' => 'btn btn-sm btn-default', 'data-dismiss' => 'modal'])
        . Html::button(
            $model->isNewRecord ? Yii::t('app', 'Добавить') : Yii::t('app', 'Сохранить'),
            ['class' => 'btn btn-sm btn-primary js-submit'],
        ),
]); ?>
<?= Html::errorSummary($model, ['class' => 'alert alert-danger']) ?>
<?php $form = ActiveForm::begin([
    'options' => ['class' => 'js-form'],
]); ?>

    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'platform_type')->widget(Select::class, [
                'items' => ClassifierRequestType::getPlatformTypeList(),
                'options' => ['class' => 'form-control'],
            ]); ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'source_id')->widget(
                ComboTree::class,
                [
                    'options' => [
                        'class' => 'btn btn-default',
                        'data-style' => '',
                        'prompt' => Yii::t('app', 'Ничего не выбрано'),
                    ],
                    'dropDownOptions' => [
                        'isMultiple' => false,
                        'cascadeSelect' => false,
                        'collapse' => true,
                    ],
                    'data' => Source::getTree(),
                ]
            ) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'request_type_id')->widget(
                ComboTree::class,
                [
                    'options' => [
                        'class' => 'btn btn-default',
                        'data-style' => '',
                        'prompt' => Yii::t('app', 'Ничего не выбрано'),
                    ],
                    'dropDownOptions' => [
                        'isMultiple' => false,
                        'cascadeSelect' => false,
                        'collapse' => true,
                    ],
                    'data' => RequestType::getTree(),
                ]
            ) ?>
        </div>
    </div>
<?php ActiveForm::end(); ?>
<?php Modal::end(); ?>