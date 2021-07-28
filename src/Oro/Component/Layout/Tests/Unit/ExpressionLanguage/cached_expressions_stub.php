<?php

return [
    'closures' => [
        'expression_1' => static function ($context, $data) {
            return 'expression_1_result';
        },
        'expression_2' => static function ($context, $data) {
            return 'expression_2_result';
        },
    ],
    'closuresWithExtraParams' => [
        'expression_3' => [
            static function ($context, $data, $param1) {
                return 'expression_3_result';
            },
            ['param1']
        ],
        'expression_4' => [
            static function ($context, $data, $param1, $param2) {
                return 'expression_4_result';
            },
            ['param1', 'param2']
        ],
    ]
];
