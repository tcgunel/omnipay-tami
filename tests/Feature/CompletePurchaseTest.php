<?php

namespace Omnipay\Tami\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Message\CompletePurchaseRequest;
use Omnipay\Tami\Message\CompletePurchaseResponse;
use Omnipay\Tami\Tests\TestCase;

class CompletePurchaseTest extends TestCase
{
	public function test_complete_purchase_request()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/CompletePurchaseRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$data = $request->getData();

		self::assertIsArray($data);
		self::assertEquals('ORDER-123456', $data['orderId']);
		self::assertArrayHasKey('securityHash', $data);
		self::assertNotEmpty($data['securityHash']);
	}

	public function test_complete_purchase_request_validation_error()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/CompletePurchaseRequest-ValidationError.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$this->expectException(InvalidRequestException::class);

		$request->getData();
	}

	public function test_complete_purchase_response_success()
	{
		$httpResponse = $this->getMockHttpResponse('CompletePurchaseResponseSuccess.txt');

		$response = new CompletePurchaseResponse($this->getMockRequest(), $httpResponse);

		$this->assertTrue($response->isSuccessful());
		$this->assertEquals('TAMI-REF-789012', $response->getTransactionReference());
	}

	public function test_complete_purchase_response_api_error()
	{
		$httpResponse = $this->getMockHttpResponse('CompletePurchaseResponseApiError.txt');

		$response = new CompletePurchaseResponse($this->getMockRequest(), $httpResponse);

		$this->assertFalse($response->isSuccessful());
		$this->assertEquals('3D dogrulama basarisiz', $response->getMessage());
	}
}
