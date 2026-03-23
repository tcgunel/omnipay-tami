<?php

namespace Omnipay\Tami;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Tami\Message\BinInstallmentRequest;
use Omnipay\Tami\Message\CancelRequest;
use Omnipay\Tami\Message\CompletePurchaseRequest;
use Omnipay\Tami\Message\PurchaseRequest;
use Omnipay\Tami\Message\RefundRequest;
use Omnipay\Tami\Traits\GettersSettersTrait;

/**
 * Tami Gateway
 * (c) Tolga Can Gunel
 * 2015, mobius.studio
 * http://www.github.com/tcgunel/omnipay-tami
 * @method \Omnipay\Common\Message\NotificationInterface acceptNotification(array $options = [])
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
}
