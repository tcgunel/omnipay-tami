<?php

namespace Omnipay\Tami\Traits;

trait GettersSettersTrait
{
    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    public function getMerchantUser()
    {
        return $this->getParameter('merchantUser');
    }

    public function setMerchantUser($value)
    {
        return $this->setParameter('merchantUser', $value);
    }

    public function getMerchantStorekey()
    {
        return $this->getParameter('merchantStorekey');
    }

    public function setMerchantStorekey($value)
    {
        return $this->setParameter('merchantStorekey', $value);
    }

    public function getMerchantPassword()
    {
        return $this->getParameter('merchantPassword');
    }

    public function setMerchantPassword($value)
    {
        return $this->setParameter('merchantPassword', $value);
    }

    public function getInstallment()
    {
        return $this->getParameter('installment');
    }

    public function setInstallment($value)
    {
        return $this->setParameter('installment', $value);
    }

    public function getOrderId()
    {
        return $this->getParameter('orderId');
    }

    public function setOrderId($value)
    {
        return $this->setParameter('orderId', $value);
    }

    public function getPaymentGroup()
    {
        return $this->getParameter('paymentGroup');
    }

    public function setPaymentGroup($value)
    {
        return $this->setParameter('paymentGroup', $value);
    }

    public function getBuyer()
    {
        return $this->getParameter('buyer');
    }

    public function setBuyer($value)
    {
        return $this->setParameter('buyer', $value);
    }

    public function getShippingAddress()
    {
        return $this->getParameter('shippingAddress');
    }

    public function setShippingAddress($value)
    {
        return $this->setParameter('shippingAddress', $value);
    }

    public function getBillingAddress()
    {
        return $this->getParameter('billingAddress');
    }

    public function setBillingAddress($value)
    {
        return $this->setParameter('billingAddress', $value);
    }

    public function getCallbackUrl()
    {
        return $this->getParameter('callbackUrl');
    }

    public function setCallbackUrl($value)
    {
        return $this->setParameter('callbackUrl', $value);
    }

    public function getSecure()
    {
        return $this->getParameter('secure');
    }

    public function setSecure($value)
    {
        return $this->setParameter('secure', $value);
    }

    public function getBinNumber()
    {
        return $this->getParameter('binNumber');
    }

    public function setBinNumber($value)
    {
        return $this->setParameter('binNumber', $value);
    }

    public function getRefundAmount()
    {
        return $this->getParameter('refundAmount');
    }

    public function setRefundAmount($value)
    {
        return $this->setParameter('refundAmount', $value);
    }

    public function getClientIp()
    {
        return $this->getParameter('clientIp');
    }

    public function setClientIp($value)
    {
        return $this->setParameter('clientIp', $value);
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }
}
