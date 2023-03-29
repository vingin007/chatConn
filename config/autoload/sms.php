<?php

return [
    'default' => [
        'gateways' => [
            'aliyun',
        ],
    ],
    'gateways' => [
        'aliyun' => [
            'access_key_id' => env('ALIYUN_ACCESS_KEY_ID'),
            'access_key_secret' => env('ALIYUN_ACCESS_KEY_SECRET'),
            'sign_name' => env('SMS_SIGN_NAME'),
        ],
        // 其他短信服务商的配置
    ],
];
