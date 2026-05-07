# Omnipay: Tami

**Tami payment gateway driver for the Omnipay PHP payment processing library.**

[Omnipay](https://github.com/thephpleague/omnipay) is a framework-agnostic, multi-gateway payment processing library for PHP. This package implements Tami support for Omnipay, including 3D Secure, partial / full cancellation and refund, BIN lookup, installment lookup, and transaction query.

The package is built against Tami's documentation at https://dev.tami.com.tr — but it also absorbs the divergences between the published docs and the actual production payloads. See the [Doc-vs-prod quirks](#doc-vs-prod-quirks-the-package-absorbs) section.

## Installation

```bash
composer require tcgunel/omnipay-tami
```

Requires PHP 8.3+ and `omnipay/common ^3.0`.

## Credentials

A merchant needs five values from Tami's portal under **İş Yerim → POS Yetkileri**:

| Tami portal label | Setter | Notes |
|---|---|---|
| Üye İş Yeri Numarası | `setMerchantId()` | 8-digit merchant number |
| Terminal Numarası | `setMerchantUser()` | 8-digit terminal number, on a separate POS Yetkileri sub-screen |
| Güvenlik Anahtarı | `setMerchantStorekey()` | UUID-style secret key, used for both `PG-Auth-Token` and 3DS callback `hashedData` HMAC |
| Kid Değeri | — | JWT `kid` for body signing (see below) |
| K Değeri | — | base64url-encoded HMAC-SHA512 key for body signing (JWK `k`) |

The two JWK fields are passed through `setMerchantPassword()` as a single `kid|k` string:

```php
$gateway->setMerchantPassword('your-kid-here|your-base64url-encoded-k-here');
```

Each request to Tami carries:

- HTTP header `PG-Auth-Token: merchantId:merchantUser:base64(sha256(merchantId + merchantUser + merchantStorekey))`
- JSON body field `securityHash` — a JWS Compact Serialization (RFC 7515) of the rest of the request body, signed with HS512 using the `k` value, with `{"alg":"HS512","typ":"JWT","kid":"<kid>"}` as the header.

## Gateway setup

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('Tami');

$gateway->setMerchantId('your-merchant-number');
$gateway->setMerchantUser('your-terminal-number');
$gateway->setMerchantStorekey('your-secret-key');
$gateway->setMerchantPassword('your-kid-here|your-base64url-encoded-k-here');
$gateway->setTestMode(true); // sandbox base URL
```

Endpoints used:

| | Sandbox | Production |
|---|---|---|
| Base URL | `https://sandbox-paymentapi.tami.com.tr` | `https://paymentapi.tami.com.tr` |

## Methods

| Method | Endpoint | Returns |
|---|---|---|
| `purchase()` | `POST /payment/auth` | `PurchaseResponse` (3DS-aware) |
| `completePurchase()` | `POST /payment/complete-3ds` | `CompletePurchaseResponse` |
| `cancel()` | `POST /payment/reverse` | `CancelResponse` (full or partial) |
| `refund()` | `POST /payment/reverse` | `RefundResponse` |
| `bin()` | `POST /installment/bin-info` | `BinResponse` (BIN metadata only) |
| `binInstallment()` | `POST /installment/installment-info` | `BinInstallmentResponse` (BIN + installment list) |
| `query()` | `POST /payment/query` | `QueryResponse` (order status, transaction history) |
| `acceptNotification()` | n/a | `Notification` (parses the 3DS callback POST) |

## Direct (non-3D) payment

```php
$response = $gateway->purchase([
    'amount' => '100.00',
    'currency' => 'TRY',
    'transactionId' => 'ORDER-123',  // becomes Tami orderId, 2..36 chars, alnum + _-
    'installment' => 1,
    'card' => [
        'firstName' => 'Ada',
        'lastName' => 'Lovelace',
        'number' => '4155650100416111',
        'expiryMonth' => '01',
        'expiryYear' => '2030',
        'cvv' => '123',
    ],
    'clientIp' => '127.0.0.1',
])->send();

if ($response->isSuccessful()) {
    echo $response->getTransactionReference();  // bankReferenceNumber
} else {
    echo $response->getMessage();
}
```

## 3D Secure payment

### Step 1 — initiate

```php
$response = $gateway->purchase([
    'amount' => '100.00',
    'currency' => 'TRY',
    'transactionId' => 'ORDER-123',
    'installment' => 1,
    'secure' => true,
    'returnUrl' => 'https://merchant.example/orders/ORDER-123/verify-payment',
    'card' => [ /* ... */ ],
    'clientIp' => '127.0.0.1',
])->send();

if ($response->isRedirect()) {
    // Tami returns base64-encoded HTML for the bank's 3D page.
    // PurchaseResponse::getRedirectResponse() decodes it and returns a
    // ready-to-emit Symfony HttpResponse — Omnipay's standard pattern works:
    return $response->getRedirectResponse();

    // Or pull the raw HTML if you need to embed it differently:
    // echo $response->getRedirectHtml();
}
```

### Step 2 — handle the callback

After the user authenticates with the bank, Tami POSTs to `returnUrl` with the 3DS verification payload. Use `acceptNotification()` to parse it — the `Notification` class hides the production wire-format quirks (see the [quirks section](#doc-vs-prod-quirks-the-package-absorbs)):

```php
$notification = $gateway->acceptNotification($_POST);

if (! $notification->isSuccessful()) {
    return failure_view($notification->getMessage());
}

if (! $notification->verifyHash($merchantStorekey)) {
    return failure_view('3DS hash verification failed');
}

// 3DS challenge succeeded — finalize the charge.
$completion = $gateway->completePurchase([
    'transactionId' => $notification->getTransactionId(),
])->send();

if ($completion->isSuccessful()) {
    // Charge captured. $completion->getTransactionReference() = bank ref number.
}
```

`Notification` exposes:

| Method | What it returns |
|---|---|
| `isSuccessful()` | `true` only when `mdStatus === '1'` AND `success ∈ {1, "1", "true", true}` |
| `getTransactionStatus()` | Omnipay constant: `STATUS_COMPLETED` or `STATUS_FAILED` |
| `getMessage()` | `mdErrorMessage` ?? `errorMessage` |
| `getTransactionId()` | the `orderId` you sent |
| `getTransactionReference()` | `bankReferenceNumber` (when present) |
| `getMdStatus()` | raw `mdStatus` string |
| `verifyHash($secretKey)` | HMAC-SHA256 check over the field list in `hashParams` |
| `getData()` | raw callback array |

## Cancel (same-day reversal)

```php
$gateway->cancel([
    'transactionId' => 'ORDER-123',
    // optional partial:
    'amount' => '12.50',
    // optional reason, capped at 150 chars:
    'description' => 'customer change of mind',
])->send();
```

## Refund (after settlement)

```php
$gateway->refund([
    'transactionId' => 'ORDER-123',
    'amount' => '50.00',
    'description' => 'partial return',  // optional, 150 chars
])->send();
```

Both endpoints are `POST /payment/reverse` — Tami switches between same-day reversal and post-settlement refund automatically.

## BIN lookup

Two endpoints, depending on what you need:

```php
// Just card metadata (bank, brand, type, commercial flag)
$bin = $gateway->bin(['binNumber' => '45438877'])->send();

$bin->getBankName();   // "T. GARANTİ BANKASI A.Ş."
$bin->getCardOrg();    // "VISA"
$bin->getCardType();   // "CREDIT"
$bin->isCommercial();  // false

// BIN metadata + merchant-permitted installment list + force3ds/forceCvc flags
$info = $gateway->binInstallment(['binNumber' => '45438877'])->send();

$info->getInstallments();  // [1, 3, 5, ...]
$info->getData();          // full payload incl. force3ds, forceCvc
```

`isInstallment` on the response indicates whether the merchant is authorized for installments at all.

## Transaction query

```php
$query = $gateway->query([
    'transactionId' => 'ORDER-123',
    'isTransactionDetail' => true,  // include the full transactions[] history
])->send();

$query->getOrderStatus();    // AUTH | REVERSE | REFUND | PARTIAL_REFUND | PRE_AUTH | POST_AUTH | CHARGEBACK
$query->getPaymentStatus();  // NOT_COMPLETE | SUCCESS | FAIL | TIME_OUT
$query->getTransactions();   // [{transactionType, transactionStatus, transactionDate, bankAuthCode, bankReferenceNumber}, ...]
```

Use this for reconciliation jobs and "did this order actually settle" checks.

## Doc-vs-prod quirks the package absorbs

Tami's published docs at https://dev.tami.com.tr are out of date in several places. The package handles all of these — you should not need to special-case them in your application code, but they're documented here so future-you knows what to expect when reading raw payloads.

**Request-side**

| Quirk | Doc says | Production wants |
|---|---|---|
| `securityHash` JWT encoding | "JWS, base64url, no padding" (implicitly, via the `nimbus-jose-jwt` Java sample) | base64url, no padding — but earlier package versions used standard base64 with padding, which Tami rejected. Fixed in v2.1.0. |
| JWT header `kid` field | RFC 7515 standard `kid` | Same. Earlier package versions sent `kidValue` instead, which Tami ignored. Fixed in v2.1.0. |
| `paymentGroup` default | `PRODUCT` | `PRODUCT`. Earlier package defaulted to `OTHER`. Fixed in v2.1.0. |
| `buyer.buyerId` | required | required. Auto-fallback now uses `transactionId` instead of an empty string. |

**3DS callback (POST to `callbackUrl`)**

| Field | Docs | Production |
|---|---|---|
| `success` | string `"true"` / `"false"` | string `"1"` / `"0"` on the wire — but Tami **signs the canonical "true"/"false" form** for `hashedData`, so the package canonicalises before HMAC |
| `hashParams` | not documented | sent on every callback; explicit list of fields used to compute `hashedData` |
| `mdErrorMessage` | not documented | sent on every callback (e.g. "Y-status/Challenge authentication via ACS") |
| Hash field list | `cardOrg`, `currency`, `originalAmount`, `orderID`, `status` | `cardOrganization`, `currencyCode`, `txnAmount`, `orderId`, `success` |
| `callbackStatus`, `transactionDate` | not documented | sometimes present |

The `Notification` class:

- Treats `success` as truthy when it's any of `1`, `"1"`, `"true"`, or boolean `true`.
- Reads `mdErrorMessage` ?? `errorMessage` for the human-readable failure reason.
- Reads `hashParams` from the callback and uses it verbatim as the field list/order for `hashedData` verification, with a sensible default if Tami ever stops sending `hashParams`.

**Purchase response**

`PurchaseResponse::getRedirectResponse()` is overridden to return a Symfony `HttpResponse` containing the **decoded** `threeDSHtmlContent`. Omnipay's default builds a self-submitting form posting to `getRedirectUrl()`, which doesn't exist for Tami — the bank-side HTML is delivered inline as base64.

## Sandbox

Sandbox base URL: `https://sandbox-paymentapi.tami.com.tr`. Sandbox portal: `https://sandbox-portal.tami.com.tr`.

Tami publishes test merchant credentials on the docs site (the `/tami-satis-islemi-3dli` page). Test cards are listed at `/test-kartlari`. Error code reference at `/hata-kodlari`.

## Testing

```bash
composer test
```

The suite runs against mocked HTTP responses; no network access required.

## License

MIT.
