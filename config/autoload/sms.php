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
        'qcloud' => [
            'sdk_app_id' => '1400808758', // 短信应用的 SDK APP ID
            'secret_id' => '', // SECRET ID
            'secret_key' => '41d6bfbcaa841df477022da0e7d6c0e5', // SECRET KEY
            'sign_name' => '智能语音对话', // 短信签名
        ],
        // 其他短信服务商的配置
    ],
];
