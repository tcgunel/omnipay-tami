<?php

namespace Omnipay\Tami\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Helpers\TamiHelper;

class RefundRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/payment/reverse';

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validateAll();

        $data = [
            'orderId' => $this->getTransactionId(),
            'amount' => round((float) $this->getAmount(), 2),
        ];

        $securityHash = TamiHelper::generateJwkSignature($this->getMerchantPassword(), $data);

        $data['securityHash'] = $securityHash;

        return $data;
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('transactionId', 'amount');
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendJsonRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): RefundResponse
    {
        return $this->response = new RefundResponse($this, $data);
    }
}
