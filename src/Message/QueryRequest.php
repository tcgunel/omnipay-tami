<?php

namespace Omnipay\Tami\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Helpers\TamiHelper;

class QueryRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/payment/query';

    public function getIsTransactionDetail()
    {
        return $this->getParameter('isTransactionDetail');
    }

    public function setIsTransactionDetail($value)
    {
        return $this->setParameter('isTransactionDetail', $value);
    }

    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validateAll();

        $data = [
            'orderId' => $this->getTransactionId(),
        ];

        if ($this->getIsTransactionDetail() !== null) {
            $data['isTransactionDetail'] = $this->getIsTransactionDetail() ? 'true' : 'false';
        }

        $data['securityHash'] = TamiHelper::generateJwkSignature($this->getMerchantPassword(), $data);

        return $data;
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('transactionId');
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendJsonRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): QueryResponse
    {
        return $this->response = new QueryResponse($this, $data);
    }
}
