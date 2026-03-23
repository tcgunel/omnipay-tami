<?php

namespace Omnipay\Tami\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Helpers\TamiHelper;

class CancelRequest extends RemoteAbstractRequest
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

		$this->validate('transactionId');
	}

	public function sendData($data)
	{
		$url = $this->getBaseUrl() . $this->endpoint;

		$httpResponse = $this->sendJsonRequest($url, $data);

		return $this->createResponse($httpResponse);
	}

	protected function createResponse($data): CancelResponse
	{
		return $this->response = new CancelResponse($this, $data);
	}
}
