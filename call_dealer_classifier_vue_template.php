<?php if (!($list = ClassifierRequestType::getList())) : ?>
    <div class="DealerClassifier">
        <div class="unityBlock_body">
            <div class="text-center py-4">
                <p class="alert alert-info">
                    <?= Yii::t('app', 'Не настроен справочник классифайдов') ?>
                    <br>
                    <small class="text-muted">
                        <?= Yii::t('app', 'Передите по {link}, чтобы настроить справочник', [
                                'link' => Html::a('ссылке', ['/ads/classifier-request-type/index']),
                        ]) ?>
                    </small>
                </p>
            </div>
        </div>
    </div>
<?php else : ?>
    <div data-component="DealerClassifier"
        data-init='<?= json_encode([
            'formName' => $model->formName(),
            'classifiers' => $model->classifiers,
            'prompt' => Yii::t('app', 'Выбрать'),
            'platformTypes' => $list,
            'canEdit' => !$isView,
        ], JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>'
    ></div>
<?php endif; ?>
