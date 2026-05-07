<?php

namespace Omnipay\Tami\Message;

use JsonException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class QueryResponse extends AbstractResponse
{
    protected $response;

    protected $request;

    protected $data;

    public function __construct(RequestInterface $request, $data)
    {
        parent::__construct($request, $data);

        $this->request = $request;
        $this->response = $data;

        if ($data instanceof ResponseInterface) {
            $body = (string) $data->getBody();

            try {
                $this->data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $this->data = [
                    'success' => false,
                    'errorMessage' => $body,
                ];
            }
        } elseif (is_array($data)) {
            $this->data = $data;
        }
    }

    public function isSuccessful(): bool
    {
        return isset($this->data['success']) && $this->data['success'] === true;
    }

    public function getOrderStatus(): ?string
    {
        return $this->data['orderStatus'] ?? null;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->data['paymentStatus'] ?? null;
    }

    public function getTransactions(): ?array
    {
        return $this->data['transactions'] ?? null;
    }

    public function getMessage(): ?string
    {
        return $this->data['errorMessage'] ?? null;
    }

    public function getData(): ?array
    {
        return $this->data;
    }
}
