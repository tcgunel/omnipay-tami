<?php

namespace Omnipay\Tami\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Message\PurchaseRequest;
use Omnipay\Tami\Message\PurchaseResponse;
use Omnipay\Tami\Tests\TestCase;

class PurchaseTest extends TestCase
{
    public function test_purchase_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertIsArray($data);
        self::assertEquals(100.00, $data['amount']);
        self::assertEquals('ORDER-123456', $data['orderId']);
        self::assertEquals('TRY', $data['currency']);
        self::assertEquals(1, $data['installmentCount']);
        self::assertEquals('PRODUCT', $data['paymentGroup']);
        self::assertArrayHasKey('card', $data);
        self::assertEquals('4155650100416111', $data['card']['number']);
        self::assertEquals('123', $data['card']['cvv']);
        self::assertArrayHasKey('buyer', $data);
        self::assertEquals('ORDER-123456', $data['buyer']['buyerId']);
        self::assertArrayHasKey('shippingAddress', $data);
        self::assertArrayHasKey('billingAddress', $data);
        self::assertArrayHasKey('securityHash', $data);
        self::assertNotEmpty($data['securityHash']);
        self::assertArrayNotHasKey('callbackUrl', $data);
    }

    public function test_purchase_request_3d_secure()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);
        $options['secure'] = true;
        $options['returnUrl'] = 'https://example.com/callback';

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertArrayHasKey('callbackUrl', $data);
        self::assertEquals('https://example.com/callback', $data['callbackUrl']);
    }

    public function test_purchase_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_purchase_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponseSuccess.txt');

        $response = new PurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('TAMI-REF-123456', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
    }

    public function test_purchase_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponseApiError.txt');

        $response = new PurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('Kart numarasi hatali', $response->getMessage());
    }

    public function test_purchase_response_3d_redirect()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponse3D.txt');

        $response = new PurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertNotNull($response->getRedirectHtml());
        $this->assertStringContainsString('3D Secure Redirect', $response->getRedirectHtml());

        $http = $response->getRedirectResponse();
        $this->assertSame($response->getRedirectHtml(), $http->getContent());
    }
}
