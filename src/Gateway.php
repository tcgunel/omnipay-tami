<?php

namespace Omnipay\Tami;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Tami\Message\BinInstallmentRequest;
use Omnipay\Tami\Message\BinRequest;
use Omnipay\Tami\Message\CancelRequest;
use Omnipay\Tami\Message\CompletePurchaseRequest;
use Omnipay\Tami\Message\Notification;
use Omnipay\Tami\Message\PurchaseRequest;
use Omnipay\Tami\Message\QueryRequest;
use Omnipay\Tami\Message\RefundRequest;
use Omnipay\Tami\Traits\GettersSettersTrait;

/**
 * Tami Gateway
 * (c) Tolga Can Gunel
 * 2015, mobius.studio
 * http://www.github.com/tcgunel/omnipay-tami
 * @method \Omnipay\Common\Message\RequestInterface completeAuthorize(array $options = [])
 */
class Gateway extends AbstractGateway
{
    use GettersSettersTrait;

    public function getName(): string
    {
        return 'Tami';
    }

    public function getDefaultParameters()
    {
        return [
            'clientIp' => '127.0.0.1',

            'merchantId' => '',
            'merchantUser' => '',
            'merchantStorekey' => '',
            'merchantPassword' => '',

            'installment' => 1,
        ];
    }

    public function purchase(array $options = []): AbstractRequest
    {
        return $this->createRequest(PurchaseRequest::class, $options);
    }

    public function completePurchase(array $options = []): AbstractRequest
    {
        return $this->createRequest(CompletePurchaseRequest::class, $options);
    }

    public function cancel(array $options = []): AbstractRequest
    {
        return $this->createRequest(CancelRequest::class, $options);
    }

    public function refund(array $options = []): AbstractRequest
    {
        return $this->createRequest(RefundRequest::class, $options);
    }

    public function binInstallment(array $options = []): AbstractRequest
    {
        return $this->createRequest(BinInstallmentRequest::class, $options);
    }

    public function bin(array $options = []): AbstractRequest
    {
        return $this->createRequest(BinRequest::class, $options);
    }

    public function query(array $options = []): AbstractRequest
    {
        return $this->createRequest(QueryRequest::class, $options);
    }

    /**
     * Wrap an incoming 3DS callbackUrl POST in a Notification that hides the
     * documented-vs-production quirks (success "1"/"true", hashParams field,
     * mdErrorMessage location, etc).
     *
     * @param array<string, mixed> $data
     */
    public function acceptNotification(array $data = []): Notification
    {
        return new Notification($data);
    }
}
