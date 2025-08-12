<?php
//some code
return [
    //some code
    'modules' => [
        //some modules
        'ads' => [
            'class' => app\modules\ads\Module::class,
            'components' => [
                'tokenManager' => [
                    'class' => \app\modules\ads\services\TokenManager::class,
                ],
                'interestService' => [
                    'class' => \app\modules\ads\services\InterestService::class,
                ],
            ],
        ],
    ],
    //some code
];
