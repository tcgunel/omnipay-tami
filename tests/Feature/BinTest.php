<?php

namespace Omnipay\Tami\Tests\Feature;

use Omnipay\Tami\Message\BinRequest;
use Omnipay\Tami\Message\BinResponse;
use Omnipay\Tami\Tests\TestCase;

class BinTest extends TestCase
{
    public function test_bin_request_targets_bin_info_endpoint()
    {
        $options = json_decode(file_get_contents(__DIR__ . '/../Mock/BinRequest.json'), true, 512, JSON_THROW_ON_ERROR);

        $request = new BinRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $data = $request->getData();

        self::assertEquals('45438877', $data['binNumber']);
        self::assertArrayHasKey('securityHash', $data);
        self::assertEquals('/installment/bin-info', $request->getEndpoint());
    }

    public function test_bin_response_exposes_card_metadata()
    {
        $http = $this->getMockHttpResponse('BinResponseSuccess.txt');

        $response = new BinResponse($this->getMockRequest(), $http);

        self::assertTrue($response->isSuccessful());
        self::assertEquals('PLACEHOLDER BANK', $response->getBankName());
        self::assertEquals('VISA', $response->getCardOrg());
        self::assertEquals('CREDIT', $response->getCardType());
        self::assertFalse($response->isCommercial());
    }
}
