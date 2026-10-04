# Testing

## Unit Tests

Unit tests قرارداد Token، Callback، Verify و Amount Matching را پوشش می‌دهند.

## Feature Test

ثبت Service Provider و resolution سرویس‌های اصلی تست می‌شود.

## Integration Test پیشنهادی برای پروژه مصرف‌کننده

در محیط staging یک پرداخت واقعی/مجاز انجام دهید و این موارد را بررسی کنید:

1. Token دریافت می‌شود.
2. Browser به SEP می‌رود.
3. Callback با POST دریافت می‌شود.
4. Verify موفق می‌شود.
5. `OrginalAmount` برابر مبلغ محلی است.
6. Order دقیقاً یک بار paid می‌شود.
7. Refresh Callback باعث fulfillment دوباره نمی‌شود.
8. در Timeout Verify، Retry انجام می‌شود.
9. در ResultCode خطا، Retry بی‌مورد انجام نمی‌شود.
10. Reverse نتیجه قابل ثبت دارد.
