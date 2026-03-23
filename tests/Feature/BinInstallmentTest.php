<?php

namespace Omnipay\Tami\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Message\BinInstallmentRequest;
use Omnipay\Tami\Message\BinInstallmentResponse;
use Omnipay\Tami\Tests\TestCase;

class BinInstallmentTest extends TestCase
{
    public function test_bin_installment_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/BinInstallmentRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new BinInstallmentRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        self::assertIsArray($data);
        self::assertEquals('415565', $data['binNumber']);
        self::assertArrayHasKey('securityHash', $data);
        self::assertNotEmpty($data['securityHash']);
    }

    public function test_bin_installment_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/BinInstallmentRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new BinInstallmentRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_bin_installment_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('BinInstallmentResponseSuccess.txt');

        $response = new BinInstallmentResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals([2, 3, 6, 9, 12], $response->getInstallments());
    }

    public function test_bin_installment_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('BinInstallmentResponseApiError.txt');

        $response = new BinInstallmentResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('Bin numarasi bulunamadi', $response->getMessage());
    }
}
