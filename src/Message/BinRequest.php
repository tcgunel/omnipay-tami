<?php

namespace Omnipay\Tami\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Helpers\TamiHelper;

class BinRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/installment/bin-info';

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validateAll();

        $data = [
            'binNumber' => $this->getBinNumber(),
        ];

        $data['securityHash'] = TamiHelper::generateJwkSignature($this->getMerchantPassword(), $data);

        return $data;
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('binNumber');
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendJsonRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): BinResponse
    {
        return $this->response = new BinResponse($this, $data);
    }
}
