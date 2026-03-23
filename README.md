# Omnipay: Tami

**Tami payment gateway driver for the Omnipay PHP payment processing library**

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment processing library for PHP. This package implements Tami support for Omnipay.

## Installation

```bash
composer require tcgunel/omnipay-tami
```

## Usage

### Gateway Setup

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('Tami');

$gateway->setMerchantId('your_merchant_id');
$gateway->setMerchantUser('your_merchant_user');
$gateway->setMerchantStorekey('your_store_key');
$gateway->setMerchantPassword('kid|base64url_encoded_key');
$gateway->setTestMode(true);
```

### Direct Payment (Non-3D)

```php
$response = $gateway->purchase([
    'amount' => '100.00',
    'currency' => 'TRY',
    'transactionId' => 'ORDER-123',
    'installment' => 1,
    'card' => [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'number' => '4155650100416111',
        'expiryMonth' => '01',
        'expiryYear' => '2030',
        'cvv' => '123',
    ],
    'clientIp' => '127.0.0.1',
])->send();

if ($response->isSuccessful()) {
    echo $response->getTransactionReference();
}
```

### 3D Secure Payment

```php
$response = $gateway->purchase([
    'amount' => '100.00',
    'currency' => 'TRY',
    'transactionId' => 'ORDER-123',
    'installment' => 1,
    'secure' => true,
    'returnUrl' => 'https://example.com/callback',
    'card' => [ /* ... */ ],
    'clientIp' => '127.0.0.1',
])->send();

if ($response->isRedirect()) {
    echo $response->getRedirectHtml(); // 3D Secure HTML form
}
```

### Complete 3D Secure

```php
$response = $gateway->completePurchase([
    'transactionId' => 'ORDER-123',
])->send();

if ($response->isSuccessful()) {
    echo $response->getTransactionReference();
}
```

### Cancel

```php
$response = $gateway->cancel([
    'transactionId' => 'ORDER-123',
])->send();

if ($response->isSuccessful()) {
    echo 'Cancelled';
}
```

### Refund

```php
$response = $gateway->refund([
    'transactionId' => 'ORDER-123',
    'amount' => '50.00',
])->send();

if ($response->isSuccessful()) {
    echo 'Refunded';
}
```

### BIN Installment Query

```php
$response = $gateway->binInstallment([
    'binNumber' => '415565',
])->send();

if ($response->isSuccessful()) {
    print_r($response->getInstallments());
}
```

## Available Methods

| Method | Description |
|--------|-------------|
| `purchase()` | Direct sale or 3D Secure initiation |
| `completePurchase()` | Complete 3D Secure payment |
| `cancel()` | Cancel/void a transaction |
| `refund()` | Partial or full refund |
| `binInstallment()` | Query installment options by BIN |

## Testing

```bash
composer test
```

## License

MIT
