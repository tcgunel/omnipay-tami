<?php

namespace Omnipay\Tami\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Message\QueryRequest;
use Omnipay\Tami\Message\QueryResponse;
use Omnipay\Tami\Tests\TestCase;

class QueryTest extends TestCase
{
    public function test_query_request_includes_order_id_and_detail_flag()
    {
        $options = json_decode(file_get_contents(__DIR__ . '/../Mock/QueryRequest.json'), true, 512, JSON_THROW_ON_ERROR);

        $request = new QueryRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $data = $request->getData();

        self::assertEquals('ORDER-123456', $data['orderId']);
        self::assertEquals('true', $data['isTransactionDetail']);
        self::assertArrayHasKey('securityHash', $data);
        self::assertNotEmpty($data['securityHash']);
    }

    public function test_query_request_omits_detail_flag_when_unset()
    {
        $options = json_decode(file_get_contents(__DIR__ . '/../Mock/QueryRequest.json'), true, 512, JSON_THROW_ON_ERROR);
        unset($options['isTransactionDetail']);

        $request = new QueryRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $data = $request->getData();

        self::assertArrayNotHasKey('isTransactionDetail', $data);
    }

    public function test_query_request_validation_error()
    {
        $options = json_decode(file_get_contents(__DIR__ . '/../Mock/QueryRequest-ValidationError.json'), true, 512, JSON_THROW_ON_ERROR);

        $request = new QueryRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function test_query_response_success()
    {
        $http = $this->getMockHttpResponse('QueryResponseSuccess.txt');

        $response = new QueryResponse($this->getMockRequest(), $http);

        self::assertTrue($response->isSuccessful());
        self::assertEquals('AUTH', $response->getOrderStatus());
        self::assertEquals('SUCCESS', $response->getPaymentStatus());
        self::assertCount(1, $response->getTransactions());
    }

    public function test_query_response_api_error()
    {
        $http = $this->getMockHttpResponse('QueryResponseApiError.txt');

        $response = new QueryResponse($this->getMockRequest(), $http);

        self::assertFalse($response->isSuccessful());
        self::assertEquals('Siparis bulunamadi', $response->getMessage());
    }
}
