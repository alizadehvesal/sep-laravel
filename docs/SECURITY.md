# Security

## PAN / CVV2 / PIN2

این پکیج هیچ endpointی برای دریافت اطلاعات کارت ندارد. این اطلاعات باید فقط در SEP وارد شوند.

## RefNum

RefNum را یک credential دائمی تصور نکنید؛ آن را receipt identifier بدانید. برای جلوگیری از replay باید consumption state در دیتابیس شما کنترل شود.

## Logs

Payloadهای SEP ممکن است شامل `SecurePan` و `HashedCardNumber` باشند. قبل از ارسال payload به logging system آن‌ها را redact کنید.

## Callback

Callback نباید به login/session وابسته باشد. اعتبار پرداخت با `VerifyTransaction` مشخص می‌شود.

## HTTPS

Production باید با HTTPS اجرا شود و certificate معتبر داشته باشد.

## Server IP

IP عمومی سرور باید با IP ثبت‌شده نزد SEP یکسان باشد.
