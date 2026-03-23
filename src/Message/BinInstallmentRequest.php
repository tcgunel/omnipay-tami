<?php

namespace Omnipay\Tami\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Helpers\TamiHelper;

class BinInstallmentRequest extends RemoteAbstractRequest
{
	protected $endpoint = '/installment/installment-info';

	/**
	 * @throws InvalidRequestException
	 */
	public function getData(): array
	{
		$this->validateAll();

		$data = [
			'binNumber' => $this->getBinNumber(),
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

		$this->validate('binNumber');
	}

	public function sendData($data)
	{
		$url = $this->getBaseUrl() . $this->endpoint;

		$httpResponse = $this->sendJsonRequest($url, $data);

		return $this->createResponse($httpResponse);
	}

	protected function createResponse($data): BinInstallmentResponse
	{
		return $this->response = new BinInstallmentResponse($this, $data);
	}
}
