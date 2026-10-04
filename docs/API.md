# API Reference

## `SepGateway`

### `requestToken(TokenRequest $request): TokenResponse`

درخواست Token مستقیم به SEP.

### `verify(string $refNum): TransactionResponse`

فراخوانی VerifyTransaction.

### `reverse(string $refNum): TransactionResponse`

فراخوانی ReverseTransaction.

### `callbackFromArray(array $payload): CallbackData`

تبدیل payload خام SEP به DTO و حداقل اعتبارسنجی callback.

### `paymentUrl(string $token): string`

ساخت URL رسمی `SendToken`.

### `paymentForm(string $token, bool $getMethod = false): string`

ساخت HTML POST form مطابق مستند SEP.

### `assertAmountMatches(int $expected, TransactionResponse $verification): void`

مقایسه مبلغ Order با `TransactionDetail.OrginalAmount`.

## `SepPaymentService`

### `createPayment(...)`

رکورد Payment ایجاد و Token دریافت می‌کند.

### `processCallback(CallbackData $callback): PaymentResult`

جریان Callback → Verify → Amount Check → Idempotent Finalization را اجرا می‌کند.

### `reverse(string $refNum): TransactionResponse`

Reverse را از طریق Gateway اجرا می‌کند.

## `PaymentRepository`

برای جایگزینی persistence، این interface را bind کنید.

متدهای لازم:

```php
createPending()
findByResNum()
findByRefNum()
markTokenCreated()
markCallback()
markVerified()
markFailed()
markReversed()
isRefNumConsumed()
```
