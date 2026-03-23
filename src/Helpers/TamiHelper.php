<?php

namespace Omnipay\Tami\Helpers;

class TamiHelper
{
	/**
	 * Generate PG-Auth-Token header value.
	 * Format: merchantId:merchantUser:sha256(merchantId + merchantUser + merchantStorekey)
	 */
	public static function generateAuthToken(string $merchantId, string $merchantUser, string $merchantStorekey): string
	{
		$hash = base64_encode(hash('sha256', $merchantId . $merchantUser . $merchantStorekey, true));

		return $merchantId . ':' . $merchantUser . ':' . $hash;
	}

	/**
	 * Generate JWK Signature (JWT-like) for request body.
	 *
	 * merchantPassword format: "kid|kValue" where kValue is base64url encoded HMAC key.
	 *
	 * @param string $merchantPassword Format: "kid|kValue"
	 * @param array $requestBody
	 * @return string JWT-like signature: header.payload.signature
	 */
	public static function generateJwkSignature(string $merchantPassword, array $requestBody): string
	{
		$parts = explode('|', $merchantPassword);

		$kidValue = $parts[0];
		$kValue = count($parts) > 1 ? $parts[1] : $parts[0];

		$bodyJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		$headerObj = [
			'alg' => 'HS512',
			'typ' => 'JWT',
			'kidValue' => $kidValue,
		];

		$headerB64 = base64_encode(json_encode($headerObj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$payloadB64 = base64_encode($bodyJson);

		$signingInput = $headerB64 . '.' . $payloadB64;

		$key = base64_decode(self::base64UrlNormalize($kValue));

		$signatureB64 = base64_encode(hash_hmac('sha512', $signingInput, $key, true));

		return $headerB64 . '.' . $payloadB64 . '.' . $signatureB64;
	}

	/**
	 * Normalize base64url to standard base64.
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
