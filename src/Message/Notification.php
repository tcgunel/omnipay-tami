<?php

namespace Omnipay\Tami\Message;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Tami\Helpers\TamiHelper;

/**
 * Wraps Tami's 3DS callbackUrl POST and exposes it through Omnipay's
 * standard NotificationInterface so consumers don't have to know that:
 *  - `success` is sent as the string "1"/"0" (not the documented "true"/"false")
 *  - `mdStatus` is sent as a string and only "1" means verified
 *  - error text lives in `mdErrorMessage` (not always `errorMessage`)
 *  - `hashedData` is computed from the fields named in the `hashParams`
 *    field, not the (out-of-date) field list published in the docs
 */
class Notification implements NotificationInterface
{
    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function isSuccessful(): bool
    {
        return $this->mdStatusOk() && $this->successFlagOk();
    }

    public function getTransactionStatus(): string
    {
        return $this->isSuccessful()
            ? NotificationInterface::STATUS_COMPLETED
            : NotificationInterface::STATUS_FAILED;
    }

    public function getMessage(): ?string
    {
        return $this->data['mdErrorMessage']
            ?? $this->data['errorMessage']
            ?? null;
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['bankReferenceNumber'] ?? null;
    }

    public function getTransactionId(): ?string
    {
        return $this->data['orderId'] ?? null;
    }

    public function getMdStatus(): ?string
    {
        return isset($this->data['mdStatus']) ? (string) $this->data['mdStatus'] : null;
    }

    /**
     * Verify the `hashedData` value against the merchant's secret key. Reads
     * the `hashParams` field from the callback when present and uses that as
     * the field list/order; otherwise falls back to a sensible default.
     */
    public function verifyHash(string $secretKey): bool
    {
        if ($secretKey === '') {
            return false;
        }

        return TamiHelper::verifyCallbackHash($this->data, $secretKey);
    }

    private function mdStatusOk(): bool
    {
        return $this->getMdStatus() === '1';
    }

    private function successFlagOk(): bool
    {
        $value = $this->data['success'] ?? null;

        if (is_bool($value)) {
            return $value === true;
        }

        return in_array((string) $value, ['1', 'true'], true);
    }
}
