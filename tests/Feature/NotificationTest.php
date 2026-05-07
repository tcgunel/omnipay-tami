<?php

namespace Omnipay\Tami\Tests\Feature;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Tami\Helpers\TamiHelper;
use Omnipay\Tami\Message\Notification;
use Omnipay\Tami\Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_is_successful_accepts_string_one_for_success_field()
    {
        $notification = new Notification([
            'mdStatus' => '1',
            'success' => '1',
        ]);

        self::assertTrue($notification->isSuccessful());
        self::assertEquals(NotificationInterface::STATUS_COMPLETED, $notification->getTransactionStatus());
    }

    public function test_is_successful_accepts_true_string_for_success_field()
    {
        $notification = new Notification([
            'mdStatus' => '1',
            'success' => 'true',
        ]);

        self::assertTrue($notification->isSuccessful());
    }

    public function test_is_successful_accepts_native_boolean_true()
    {
        $notification = new Notification([
            'mdStatus' => '1',
            'success' => true,
        ]);

        self::assertTrue($notification->isSuccessful());
    }

    public function test_md_status_other_than_one_fails()
    {
        foreach (['0', '2', '3', '4', '5', '6', '7', '8'] as $bad) {
            $notification = new Notification([
                'mdStatus' => $bad,
                'success' => '1',
            ]);

            self::assertFalse($notification->isSuccessful(), "mdStatus=$bad must fail");
            self::assertEquals(NotificationInterface::STATUS_FAILED, $notification->getTransactionStatus());
        }
    }

    public function test_get_message_prefers_md_error_message()
    {
        $notification = new Notification([
            'mdErrorMessage' => 'Y-status/Challenge authentication via ACS',
            'errorMessage' => 'fallback',
        ]);

        self::assertEquals('Y-status/Challenge authentication via ACS', $notification->getMessage());
    }

    public function test_verify_hash_using_callback_hash_params_field()
    {
        $secretKey = 'placeholder-secret';

        $callback = [
            'cardOrganization' => 'MASTERCARD',
            'cardBrand' => 'PLACEHOLDER BANK',
            'cardType' => 'CREDIT',
            'maskedNumber' => '555555******5555',
            'installmentCount' => '1',
            'currencyCode' => 'TRY',
            'txnAmount' => '1',
            'orderId' => 'order-123',
            'systemTime' => '2026-01-01T00:00:00.000',
            'success' => '1',
            'mdStatus' => '1',
            'hashParams' => 'cardOrganization+cardBrand+cardType+maskedNumber+installmentCount+currencyCode+txnAmount+orderId+systemTime+success',
        ];

        $callback['hashedData'] = TamiHelper::computeCallbackHash($callback, $secretKey);

        $notification = new Notification($callback);

        self::assertTrue($notification->verifyHash($secretKey));
        self::assertFalse($notification->verifyHash('wrong-secret'));
    }

    public function test_verify_hash_rejects_empty_secret()
    {
        $notification = new Notification(['hashedData' => 'whatever']);

        self::assertFalse($notification->verifyHash(''));
    }

    public function test_get_transaction_id_returns_order_id()
    {
        $notification = new Notification(['orderId' => 'ORDER-1']);

        self::assertEquals('ORDER-1', $notification->getTransactionId());
    }
}
