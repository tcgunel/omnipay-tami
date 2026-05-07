<?php

namespace Omnipay\Tami\Message;

use Omnipay\Common\Exception\InvalidCreditCardException;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tami\Helpers\TamiHelper;

class PurchaseRequest extends RemoteAbstractRequest
{
    protected $endpoint = '/payment/auth';

    /**
     * @throws InvalidRequestException
     * @throws InvalidCreditCardException
     */
    public function getData(): array
    {
        $this->validateAll();

        $installment = $this->getInstallment();
        $installment = $installment > 1 ? (int) $installment : 1;

        $data = [
            'amount' => round((float) $this->getAmount(), 2),
            'orderId' => $this->getTransactionId(),
            'currency' => $this->getCurrency() ?? 'TRY',
            'installmentCount' => $installment,
            'paymentGroup' => $this->getPaymentGroup() ?? 'PRODUCT',
            'card' => [
                'holderName' => $this->get_card('getName'),
                'cvv' => $this->get_card('getCvv'),
                'number' => $this->get_card('getNumber'),
                'expireMonth' => $this->get_card('getExpiryMonth'),
                'expireYear' => $this->get_card('getExpiryYear'),
            ],
            'buyer' => $this->getBuyer() ?? [
                'buyerId' => $this->getTransactionId(),
                'ipAddress' => $this->getClientIp() ?? '127.0.0.1',
                'name' => $this->get_card('getFirstName') ?? '',
                'surName' => $this->get_card('getLastName') ?? '',
                'city' => $this->get_card('getBillingCity') ?? '',
                'country' => $this->get_card('getBillingCountry') ?? 'TR',
                'zipCode' => $this->get_card('getBillingPostcode') ?? '',
                'emailAddress' => $this->get_card('getEmail') ?? '',
                'phoneNumber' => $this->get_card('getPhone') ?? '',
                'registrationAddress' => $this->get_card('getBillingAddress1') ?? '',
            ],
            'shippingAddress' => $this->getShippingAddress() ?? [
                'emailAddress' => $this->get_card('getEmail') ?? '',
                'address' => $this->get_card('getShippingAddress1') ?? '',
                'city' => $this->get_card('getShippingCity') ?? '',
                'companyName' => trim(($this->get_card('getFirstName') ?? '') . ' ' . ($this->get_card('getLastName') ?? '')),
                'country' => $this->get_card('getShippingCountry') ?? 'TR',
                'district' => '',
                'contactName' => trim(($this->get_card('getFirstName') ?? '') . ' ' . ($this->get_card('getLastName') ?? '')),
                'phoneNumber' => $this->get_card('getShippingPhone') ?? '',
                'zipCode' => $this->get_card('getShippingPostcode') ?? '',
            ],
            'billingAddress' => $this->getBillingAddress() ?? [
                'emailAddress' => $this->get_card('getEmail') ?? '',
                'address' => $this->get_card('getBillingAddress1') ?? '',
                'city' => $this->get_card('getBillingCity') ?? '',
                'companyName' => trim(($this->get_card('getFirstName') ?? '') . ' ' . ($this->get_card('getLastName') ?? '')),
                'country' => $this->get_card('getBillingCountry') ?? 'TR',
                'district' => '',
                'contactName' => trim(($this->get_card('getFirstName') ?? '') . ' ' . ($this->get_card('getLastName') ?? '')),
                'phoneNumber' => $this->get_card('getBillingPhone') ?? $this->get_card('getPhone') ?? '',
                'zipCode' => $this->get_card('getBillingPostcode') ?? '',
            ],
        ];

        if ($this->getSecure()) {
            $data['callbackUrl'] = $this->getCallbackUrl() ?? $this->getReturnUrl();
        }

        $securityHash = TamiHelper::generateJwkSignature($this->getMerchantPassword(), $data);

        $data['securityHash'] = $securityHash;

        return $data;
    }

    /**
     * @throws InvalidRequestException
     * @throws InvalidCreditCardException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('amount', 'transactionId', 'card');

        $this->getCard()->validate();
    }

    public function sendData($data)
    {
        $url = $this->getBaseUrl() . $this->endpoint;

        $httpResponse = $this->sendJsonRequest($url, $data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): PurchaseResponse
    {
        return $this->response = new PurchaseResponse($this, $data);
    }
}
