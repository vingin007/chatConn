<?php
namespace App\Service;
class SignService
{
    private string $secret;
    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function generateSign($timestamp): string
    {
        $beforeSign = $timestamp . "\n" . $this->secret;
        $sign = hash_hmac('sha256', $beforeSign, $this->secret, true);
        return urlencode(base64_encode($sign));
    }
}