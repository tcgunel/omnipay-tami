<?php

namespace Omnipay\Tami\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Tami\Helpers\TamiHelper;
use Omnipay\Tami\Traits\GettersSettersTrait;

abstract class RemoteAbstractRequest extends AbstractRequest
{
    use GettersSettersTrait;

    protected $endpointTest = 'https://sandbox-paymentapi.tami.com.tr';

    protected $endpointLive = 'https://paymentapi.tami.com.tr';

    protected $endpoint = '';

    /**
     * @throws InvalidRequestException
     */
    protected function validateSettings(): void
    {
        $this->validate('merchantId', 'merchantUser', 'merchantStorekey', 'merchantPassword');
    }

    protected function getBaseUrl(): string
    {
        return $this->getTestMode() ? $this->endpointTest : $this->endpointLive;
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'tr',
            'PG-Api-Version' => 'v3',
            'PG-Auth-Token' => TamiHelper::generateAuthToken(
                $this->getMerchantId(),
                $this->getMerchantUser(),
                $this->getMerchantStorekey()
            ),
            'correlationId' => 'Correlation' . bin2hex(random_bytes(16)),
        ];
    }

    protected function sendJsonRequest(string $url, array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->httpClient->request(
            'POST',
            $url,
            $this->getHeaders(),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function get_card($key)
    {
        return $this->getCard() ? $this->getCard()->$key() : null;
    }

    abstract protected function createResponse($data);
}
