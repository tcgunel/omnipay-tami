<?php

namespace Omnipay\Tami\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Message\RefundRequest;
use Omnipay\Tami\Message\RefundResponse;
use Omnipay\Tami\Tests\TestCase;

class RefundTest extends TestCase
{
    public function test_refund_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/RefundRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertIsArray($data);
        self::assertEquals('ORDER-123456', $data['orderId']);
        self::assertEquals(50.00, $data['amount']);
        self::assertArrayHasKey('securityHash', $data);
        self::assertNotEmpty($data['securityHash']);
    }

    public function test_refund_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/RefundRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_refund_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('RefundResponseSuccess.txt');

        $response = new RefundResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertNull($response->getMessage());
    }

    public function test_refund_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('RefundResponseApiError.txt');

        $response = new RefundResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('Iade islemi basarisiz', $response->getMessage());
    }
}
