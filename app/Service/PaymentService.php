<?php
namespace App\Service;

use GuzzleHttp\Exception\GuzzleException;

class PaymentService
{
    private $apiUrl = 'https://codepay.9xo.top/mapi.php';
    private $pid; // 商户ID
    private $key; // 商户密钥

    public function __construct()
    {
        $this->key = 'N273073Z20FbWH6Hm2NAH6PbnM6w4HFS';
        $this->pid = '1027';
    }

    // 其他方法
    public function createPayment(array $params): array
    {
        $params['pid'] = $this->pid;
        $params['sign_type'] = 'MD5';
        $params['sign'] = $this->generateSign($params);

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->post($this->apiUrl, [
                'form_params' => $params
            ]);
        } catch (GuzzleException $e) {
            throw $e;
        }
        $result = json_decode($response->getBody(), true);
        if ($result['code'] !== 1){
            throw new \Exception($result['msg']);
        }
        return ['payurl' => $result['payurl'],'qrcode' => $result['qrcode'],'urlscheme' => $result['urlscheme']];
    }

    private function generateSign(array $params): string
    {
        ksort($params);

        $signStr = '';
        foreach ($params as $key => $value) {
            if ($value === '' || $key === 'sign' || $key === 'sign_type') {
                continue;
            }
            $signStr .= $key . '=' . $value . '&';
        }
        $signStr = rtrim($signStr, '&') . $this->key;

        return md5($signStr);
    }

    public function verifySign(array $params): bool
    {
        $receivedSign = $params['sign'] ?? '';
        $calculatedSign = $this->generateSign($params);

        return $receivedSign === $calculatedSign;
    }
}



