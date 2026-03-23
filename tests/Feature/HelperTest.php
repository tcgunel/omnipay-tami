<?php

namespace Omnipay\Tami\Tests\Feature;

use Omnipay\Tami\Helpers\TamiHelper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function test_generate_auth_token()
    {
        $token = TamiHelper::generateAuthToken('merchantId', 'merchantUser', 'storeKey');

        self::assertStringStartsWith('merchantId:merchantUser:', $token);

        $parts = explode(':', $token);
        self::assertCount(3, $parts);
        self::assertEquals('merchantId', $parts[0]);
        self::assertEquals('merchantUser', $parts[1]);
        self::assertNotEmpty($parts[2]);
    }

    public function test_generate_jwk_signature()
    {
        $password = 'testKid|dGVzdEtleVZhbHVl';
        $body = ['orderId' => 'TEST-001'];

        $signature = TamiHelper::generateJwkSignature($password, $body);

        $parts = explode('.', $signature);
        self::assertCount(3, $parts);

        $header = json_decode(base64_decode($parts[0]), true);
        self::assertEquals('HS512', $header['alg']);
        self::assertEquals('JWT', $header['typ']);
        self::assertEquals('testKid', $header['kidValue']);

        $payload = json_decode(base64_decode($parts[1]), true);
        self::assertEquals('TEST-001', $payload['orderId']);
    }

    public function test_generate_jwk_signature_single_part_password()
    {
        $password = 'dGVzdEtleVZhbHVl';
        $body = ['orderId' => 'TEST-002'];

        $signature = TamiHelper::generateJwkSignature($password, $body);

        $parts = explode('.', $signature);
        self::assertCount(3, $parts);

        $header = json_decode(base64_decode($parts[0]), true);
        self::assertEquals('dGVzdEtleVZhbHVl', $header['kidValue']);
    }

    public function test_base64_url_normalize()
    {
        self::assertEquals('ab+c/d==', TamiHelper::base64UrlNormalize('ab-c_d'));
        self::assertEquals('abc=', TamiHelper::base64UrlNormalize('abc'));
        self::assertEquals('abcd', TamiHelper::base64UrlNormalize('abcd'));
    }
}
