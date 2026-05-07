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

    public function test_generate_jwk_signature_uses_base64url_and_kid_header()
    {
        $password = 'testKid|dGVzdEtleVZhbHVl';
        $body = ['orderId' => 'TEST-001'];

        $signature = TamiHelper::generateJwkSignature($password, $body);

        $parts = explode('.', $signature);
        self::assertCount(3, $parts);

        foreach ($parts as $part) {
            self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $part, 'JWT components must be base64url with no padding');
        }

        $header = json_decode(base64_decode(TamiHelper::base64UrlNormalize($parts[0])), true);
        self::assertEquals('HS512', $header['alg']);
        self::assertEquals('JWT', $header['typ']);
        self::assertEquals('testKid', $header['kid']);
        self::assertArrayNotHasKey('kidValue', $header);

        $payload = json_decode(base64_decode(TamiHelper::base64UrlNormalize($parts[1])), true);
        self::assertEquals('TEST-001', $payload['orderId']);
    }

    public function test_generate_jwk_signature_single_part_password()
    {
        $password = 'dGVzdEtleVZhbHVl';
        $body = ['orderId' => 'TEST-002'];

        $signature = TamiHelper::generateJwkSignature($password, $body);

        $parts = explode('.', $signature);
        self::assertCount(3, $parts);

        $header = json_decode(base64_decode(TamiHelper::base64UrlNormalize($parts[0])), true);
        self::assertEquals('dGVzdEtleVZhbHVl', $header['kid']);
    }

    public function test_generate_jwk_signature_matches_known_vector()
    {
        // Cross-checked against a Node.js `jose.SignJWT` run with the same key
        // and payload to lock the wire format.
        $password = 'sample-kid|c2VjcmV0LWtleS1tYXRlcmlhbA';
        $body = ['orderId' => 'ORDER-1', 'amount' => 1];

        $signature = TamiHelper::generateJwkSignature($password, $body);

        [$headerB64, $payloadB64, $signatureB64] = explode('.', $signature);

        $headerJson = json_encode(['alg' => 'HS512', 'typ' => 'JWT', 'kid' => 'sample-kid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payloadJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expectedHeaderB64 = TamiHelper::base64UrlEncode($headerJson);
        $expectedPayloadB64 = TamiHelper::base64UrlEncode($payloadJson);
        $expectedSig = TamiHelper::base64UrlEncode(
            hash_hmac('sha512', $expectedHeaderB64 . '.' . $expectedPayloadB64, base64_decode(TamiHelper::base64UrlNormalize('c2VjcmV0LWtleS1tYXRlcmlhbA')), true)
        );

        self::assertEquals($expectedHeaderB64, $headerB64);
        self::assertEquals($expectedPayloadB64, $payloadB64);
        self::assertEquals($expectedSig, $signatureB64);
    }

    public function test_base64_url_encode_strips_padding_and_swaps_alphabet()
    {
        // Standard base64 of "ab+c/d" rendered as base64url.
        self::assertEquals('YWIrYy9k', TamiHelper::base64UrlEncode('ab+c/d'));

        // Bytes that produce '+' and '/' in standard base64.
        self::assertEquals('-_8', TamiHelper::base64UrlEncode("\xfb\xff"));
    }

    public function test_base64_url_normalize()
    {
        self::assertEquals('ab+c/d==', TamiHelper::base64UrlNormalize('ab-c_d'));
        self::assertEquals('abc=', TamiHelper::base64UrlNormalize('abc'));
        self::assertEquals('abcd', TamiHelper::base64UrlNormalize('abcd'));
    }

    public function test_verify_callback_hash_round_trip()
    {
        $secretKey = 'placeholder-secret';
        $callback = [
            'cardOrganization' => 'VISA',
            'cardBrand' => 'BONUS',
            'cardType' => 'CREDIT',
            'maskedNumber' => '482491******7014',
            'installmentCount' => 1,
            'currencyCode' => 'TRY',
            'txnAmount' => 415,
            'orderId' => 'order-123',
            'systemTime' => '2026-01-01T00:00:00.000',
            'success' => '1',
        ];

        $callback['hashedData'] = TamiHelper::computeCallbackHash($callback, $secretKey);

        self::assertTrue(TamiHelper::verifyCallbackHash($callback, $secretKey));

        $callback['hashedData'] = 'tampered';
        self::assertFalse(TamiHelper::verifyCallbackHash($callback, $secretKey));
    }

    public function test_callback_hash_canonicalises_success_boolean()
    {
        $secretKey = 'placeholder-secret';

        // Tami signs the boolean as "true"/"false" even when the wire
        // payload sends "1"/"0", so the same hash must be produced for any
        // representation of true.
        $base = [
            'success' => '1',
            'orderId' => 'order-1',
            'hashParams' => 'success+orderId',
        ];

        $hashStringOne = TamiHelper::computeCallbackHash($base, $secretKey);
        $hashStringTrue = TamiHelper::computeCallbackHash(array_merge($base, ['success' => 'true']), $secretKey);
        $hashBoolTrue = TamiHelper::computeCallbackHash(array_merge($base, ['success' => true]), $secretKey);
        $hashIntOne = TamiHelper::computeCallbackHash(array_merge($base, ['success' => 1]), $secretKey);

        self::assertEquals($hashStringOne, $hashStringTrue);
        self::assertEquals($hashStringOne, $hashBoolTrue);
        self::assertEquals($hashStringOne, $hashIntOne);

        // Sanity: should explicitly sign "true", not "1".
        $expected = base64_encode(hash_hmac('sha256', 'trueorder-1', $secretKey, true));
        self::assertEquals($expected, $hashStringOne);
    }

    public function test_verify_callback_hash_honors_hash_params_field()
    {
        $secretKey = 'placeholder-secret';

        // hashParams orders fields differently than the default and includes
        // a custom one Tami may add later. The helper must follow it verbatim.
        // `success` is also canonicalised from "1" → "true" before hashing.
        $callback = [
            'orderId' => 'order-123',
            'success' => '1',
            'cardOrganization' => 'VISA',
            'maskedNumber' => '482491******7014',
            'systemTime' => '2026-01-01T00:00:00.000',
            'hashParams' => 'success+orderId+cardOrganization+maskedNumber+systemTime',
        ];

        $expected = base64_encode(hash_hmac(
            'sha256',
            'true' . 'order-123' . 'VISA' . '482491******7014' . '2026-01-01T00:00:00.000',
            $secretKey,
            true
        ));

        self::assertEquals($expected, TamiHelper::computeCallbackHash($callback, $secretKey));
    }

    public function test_verify_callback_hash_fails_on_missing_field()
    {
        self::assertFalse(TamiHelper::verifyCallbackHash([], 'placeholder-secret'));
    }
}
