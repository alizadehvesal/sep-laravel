# Architecture

## هدف

پکیج بین منطق کسب‌وکار و API مستقیم SEP یک مرز مشخص ایجاد می‌کند.

```text
Application
    │
    ▼
SepPaymentService
    │
    ├── PaymentRepository
    │
    └── SepGateway
           │
           ▼
      SepHttpClient
           │
           ▼
          SEP
```

## چرا این تفکیک؟

- `SepGateway`: فقط قرارداد SEP را می‌شناسد.
- `SepHttpClient`: فقط transport را مدیریت می‌کند.
- `SepPaymentService`: orchestration، idempotency و amount validation را مدیریت می‌کند.
- `PaymentRepository`: persistence قابل تعویض است.
- DTOها: قراردادهای ورودی/خروجی را type-safe می‌کنند.

## قانون مهم

`SepGateway` نباید Order را paid کند. پرداخت‌کردن Order متعلق به Application است.

بهتر است Application پس از دریافت `PaymentResult::$paid === true`، در transaction خودش:

1. Order را lock کند.
2. بررسی کند Order قبلاً fulfill نشده باشد.
3. Payment را به عنوان paid ثبت کند.
4. موجودی/خدمت را فقط یک بار تحویل دهد.

این پکیج deliberately منطق business fulfillment را وارد Gateway نکرده است.
