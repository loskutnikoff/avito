<?php

use app\models\Source;
use app\modules\ads\models\search\ClassifierRequestTypeSearch;
use app\modules\lms\models\RequestType;
use app\widgets\ComboTree;
use app\widgets\Select;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View $this
 * @var ClassifierRequestTypeSearch $model
 */
?>
<?php
$form = ActiveForm::begin([
    'action' => ['index'],
    'method' => 'GET',
    'options' => ['class' => 'js-filter-form js-allSelect-formFree css-formFree'],
]);
?>
    <div class="section-filters js-section-filters mb-10" style="display: none;">
        <div class="section-filters-body">
            <div class="section-filters-body-row">
                <h4><?= Yii::t('app', 'Общие данные') ?></h4>
                <div class="row">
                    <div class="col-sm-2">
                        <?= $form->field($model, 'id')->textInput(['class' => 'form-control']); ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $form->field($model, 'platform_type')->widget(
                            Select::class,
                            [

                                'options' => [
                                    'class' => 'form-control input-sm',
                                    'prompt' => 'Выберите платформу',
                                    'data-style' => 'btn-sm btn-default',
                                ],
                                'items' => ClassifierRequestTypeSearch::getPlatformTypeList(),
                            ]
                        ); ?>
                    </div>
                    <div class="col-sm-3">
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
                    <div class="col-sm-3">
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
            </div>
        </div>
        <div class="section-filters-footer">
            <?= Html::submitButton(Yii::t('app', 'Найти'), ['class' => 'btn-primary']) ?>
            <?= Html::a(Yii::t('app', 'Сбросить'), ['index'], ['class' => 'btn-default ml-10']) ?>
        </div>
    </div>
<?php
ActiveForm::end();