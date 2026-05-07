<?php

namespace Omnipay\Tami\Helpers;

class TamiHelper
{
    /**
     * Generate PG-Auth-Token header value.
     * Format: merchantId:merchantUser:base64(sha256_raw(merchantId + merchantUser + merchantStorekey))
     */
    public static function generateAuthToken(string $merchantId, string $merchantUser, string $merchantStorekey): string
    {
        $hash = base64_encode(hash('sha256', $merchantId . $merchantUser . $merchantStorekey, true));

        return $merchantId . ':' . $merchantUser . ':' . $hash;
    }

    /**
     * Generate JWS Compact Serialization for the request body to be sent in the
     * `securityHash` field. RFC 7515 / 7518: header.payload.signature, base64url, no padding.
     *
     * merchantPassword format: "kid|kValue" where kValue is the base64url-encoded JWK "k".
     */
    public static function generateJwkSignature(string $merchantPassword, array $requestBody): string
    {
        $parts = explode('|', $merchantPassword);

        $kid = $parts[0];
        $kValue = count($parts) > 1 ? $parts[1] : $parts[0];

        $bodyJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headerJson = json_encode([
            'alg' => 'HS512',
            'typ' => 'JWT',
            'kid' => $kid,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headerB64 = self::base64UrlEncode($headerJson);
        $payloadB64 = self::base64UrlEncode($bodyJson);

        $signingInput = $headerB64 . '.' . $payloadB64;

        $key = base64_decode(self::base64UrlNormalize($kValue));

        $signatureB64 = self::base64UrlEncode(hash_hmac('sha512', $signingInput, $key, true));

        return $headerB64 . '.' . $payloadB64 . '.' . $signatureB64;
    }

    /**
     * Verify the `hashedData` Tami posts back to the merchant's 3DS callbackUrl.
     *
     * data = cardOrg + cardBrand + cardType + maskedNumber + installmentCount
     *      + currency + originalAmount + orderID + systemTime + status
     * hashedData = base64(HMAC-SHA256(secretKey, data))
     */
    public static function verifyCallbackHash(array $callback, string $secretKey): bool
    {
        $expected = self::computeCallbackHash($callback, $secretKey);
        $received = (string) ($callback['hashedData'] ?? '');

        if ($expected === '' || $received === '') {
            return false;
        }

        return hash_equals($expected, $received);
    }

    /**
     * Compute the expected `hashedData` value for a 3DS callback payload.
     *
     * Tami includes a `hashParams` field describing the exact concatenation
     * order it used. Honor it when present — the documentation's field list
     * is out of date relative to production payloads (e.g. real callbacks
     * use `currencyCode`/`txnAmount`/`success`, not `currency`/`originalAmount`/`status`).
     */
    public static function computeCallbackHash(array $callback, string $secretKey): string
    {
        $defaultParams = 'cardOrganization+cardBrand+cardType+maskedNumber+installmentCount+currencyCode+txnAmount+orderId+systemTime+success';
        $params = (string) ($callback['hashParams'] ?? $defaultParams);

        $data = '';

        foreach (explode('+', $params) as $field) {
            $field = trim($field);

            if ($field === '') {
                continue;
            }

            $data .= (string) ($callback[$field] ?? '');
        }

        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    /**
     * Base64url encode (RFC 4648 §5) — no padding, '+' → '-', '/' → '_'.
     */
    public static function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    /**
     * Convert a base64url string to standard base64 (with padding) so PHP's
     * native base64_decode can consume it.
     */
    public static function base64UrlNormalize(string $base64Url): string
    {
        $base64 = str_replace(['-', '_'], ['+', '/'], $base64Url);

        $mod = strlen($base64) % 4;

        if ($mod === 2) {
            $base64 .= '==';
        } elseif ($mod === 3) {
            $base64 .= '=';
        }

        return $base64;
    }
}
