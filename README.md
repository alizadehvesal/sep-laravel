# Laravel SEP Direct Payment Gateway

یک پکیج Laravel برای اتصال مستقیم به **درگاه پرداخت اینترنتی شرکت پرداخت الکترونیک سامان کیش (SEP)**، بدون پرداخت‌یار و بدون واسط.

> **مبنای فنی:** مستند رسمی «راهنمای استفاده از درگاه پرداخت اینترنتی – مستند فنی نگارش 3.6» شرکت پرداخت الکترونیک سامان، آخرین به‌روزرسانی دی 1404.

## ویژگی‌ها

- Token-based SEP flow مطابق مستند رسمی 3.6
- Token Request از طریق REST/JSON
- انتقال کاربر به SEP با POST Form یا GET `SendToken`
- Callback با پشتیبانی POST و GET برای `GetMethod=true`
- VerifyTransaction مستقیم روی SEP
- Retry فقط در حالت عدم دریافت پاسخ Verify؛ نه در پاسخ خطا
- بررسی سه‌گانه `Success + ResultCode + OrginalAmount`
- جلوگیری از مصرف دوباره `RefNum`
- قفل تراکنش هنگام نهایی‌سازی پرداخت
- ReverseTransaction
- DTOهای strongly-typed برای Request/Response/Callback
- Exceptionهای تخصصی
- Repository قابل جایگزینی
- Migration آماده
- تست‌های PHPUnit
- بدون ذخیره PAN/CVV/CVV2/PIN2
- لاگ/Response payload قابل کنترل و redaction-friendly
- مناسب Laravel 10 تا 13 و PHP 8.2+

## هشدار مهم

این پکیج یک لایه فنی برای درگاه مستقیم SEP است. قبل از Production باید `TerminalId`، IP سرور، URL بازگشت و الزامات پذیرندگی توسط SEP تأیید شده باشد.

**هیچ‌گاه فقط با `State=OK` سفارش را پرداخت‌شده نکنید.** طبق مستند SEP باید VerifyTransaction اجرا و پاسخ آن، از جمله مبلغ، بررسی شود.

---

## نصب

```bash
composer require vestra/laravel-sep
```

سپس:

```bash
php artisan vendor:publish --tag=sep-config
php artisan vendor:publish --tag=sep-migrations
php artisan migrate
```

## تنظیمات `.env`

```env
SEP_TERMINAL_ID=123456

SEP_TOKEN_URL=https://sep.shaparak.ir/onlinepg/onlinepg
SEP_PAYMENT_URL=https://sep.shaparak.ir/OnlinePG/OnlinePG
SEP_SEND_TOKEN_URL=https://sep.shaparak.ir/OnlinePG/SendToken
SEP_VERIFY_URL=https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction
SEP_REVERSE_URL=https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction

SEP_TIMEOUT=15
SEP_CONNECT_TIMEOUT=5
SEP_VERIFY_RETRIES=3
SEP_VERIFY_RETRY_DELAY_MS=1000
SEP_TOKEN_EXPIRY_MINUTES=20

SEP_CALLBACK_ROUTE=payment/sep/callback
SEP_PUBLISH_ROUTES=true
SEP_DATABASE_ENABLED=true
SEP_PAYMENTS_TABLE=sep_payments
```

> در Production، `SEP_TERMINAL_ID` را فقط روی سرور نگه دارید و هرگز در JavaScript یا HTML ثابت قرار ندهید.

---

# جریان رسمی SEP

```text
Your Server
   │
   ├── POST Token Request ──────────────► SEP
   │                                      │
   │◄──────────── token ─────────────────┘
   │
   ├── Browser POST/GET ────────────────► SEP Payment Page
   │                                      │
   │                         Customer pays
   │                                      │
   │◄──────────── Callback ──────────────┘
   │
   ├── POST VerifyTransaction ──────────► SEP
   │◄──────────── Verify result ──────────┘
   │
   └── Match amount + finalize order
```

## نکته امنیتی کلیدی

اطلاعات کارت در سایت SEP وارد می‌شود. این پکیج عمداً هیچ API برای دریافت PAN/CVV2/PIN2 ندارد.

---

# استفاده پایه

```php
use Vestra\Sep\Services\SepPaymentService;

public function pay(SepPaymentService $payments)
{
    $payment = $payments->createPayment(
        orderId: $order->id,
        amountRial: 15000000,
        resNum: 'ORDER-'.$order->id,
        redirectUrl: route('sep.callback'),
        meta: ['order_uuid' => $order->uuid],
        cellNumber: $order->mobile,
    );

    return response()->view('payment.redirect', [
        'html' => app(\Vestra\Sep\Services\SepGateway::class)
            ->paymentForm($payment->token),
    ]);
}
```

یا انتقال با GET:

```php
return redirect()->away(
    app(\Vestra\Sep\Services\SepGateway::class)->paymentUrl($payment->token)
);
```

## مبلغ

SEP مبلغ را به **ریال** و به صورت integer دریافت می‌کند.

مثلاً:

```php
$amountRial = $amountToman * 10;
```

تبدیل مبلغ را در یک نقطه مرکزی پروژه انجام دهید و همان مقدار ریالی را برای Order و SEP استفاده کنید.

---

# Callback

پکیج route زیر را منتشر می‌کند:

```text
POST|GET /payment/sep/callback
```

در حالت استاندارد SEP، Callback با POST ارسال می‌شود.

اگر هنگام انتقال به درگاه:

```text
GetMethod=true
```

ارسال شود، SEP نتیجه را با GET و QueryString برمی‌گرداند.

در هر دو حالت، پکیج Callback را به `CallbackData` تبدیل می‌کند.

### پارامترهای اصلی Callback

- `Token`
- `ResNum`
- `RefNum`
- `TraceNo`
- `State`
- `Status`
- `TerminalId`
- `MID`
- `RRN`
- `Amount`
- `Wage`
- `AffectiveAmount`
- `SecurePan`
- `HashedCardNumber`

**RefNum را حتماً در دیتابیس خود نگهداری و مصرف‌شدن آن را مدیریت کنید.** SEP می‌تواند یک RefNum را چند بار برای Verify معتبر گزارش کند؛ مصرف‌شدن در سمت پذیرنده کنترل می‌شود.

---

# Verify

پس از Callback موفق، پکیج:

```http
POST https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction
```

را با:

```json
{
  "RefNum": "...",
  "TerminalNumber": 123456
}
```

فراخوانی می‌کند.

## شرط پرداخت قطعی

```text
Callback State = OK
        AND
Verify Success = true
        AND
Verify ResultCode = 0
        AND
Verify TransactionDetail.OrginalAmount = local payment amount
```

فقط در این حالت سفارش باید `paid` شود.

---

# Retry Verify

مستند SEP می‌گوید اگر Verify به علت Timeout/Network پاسخ نرسید، درخواست باید مجدداً تلاش شود. این پکیج به صورت پیش‌فرض 3 تلاش انجام می‌دهد.

اما اگر SEP پاسخ واقعی با `ResultCode` خطا برگرداند، پکیج آن را Timeout فرض نمی‌کند و بی‌دلیل Retry نمی‌کند.

تنظیم:

```env
SEP_VERIFY_RETRIES=3
SEP_VERIFY_RETRY_DELAY_MS=1000
```

---

# Amount Verification

اگر Order:

```text
15,000,000 IRR
```

باشد و SEP در Verify مقدار دیگری برگرداند، پرداخت **موفق نخواهد شد** و `AmountMismatchException` ایجاد می‌شود.

این بررسی برای جلوگیری از تحویل کالا/خدمت در تراکنشی با مبلغ نامعتبر ضروری است.

---

# Idempotency و RefNum

این پکیج دو سطح کنترل دارد:

1. `res_num` در دیتابیس Unique است.
2. `ref_num` در دیتابیس Unique است.
3. Callback در Transaction بررسی می‌شود.
4. هنگام نهایی‌سازی پرداخت، رکورد با `lockForUpdate()` قفل می‌شود.
5. اگر RefNum متعلق به Payment دیگری باشد، `PaymentAlreadyProcessedException` صادر می‌شود.

بنابراین Refresh یا ارسال مجدد Callback نباید باعث تحویل دوباره شود.

---

# Reverse

برای برگشت تراکنش:

```php
use Vestra\Sep\Services\SepGateway;

$result = app(SepGateway::class)->reverse($refNum);

if ($result->successful()) {
    // mark reversed
}
```

Endpoint:

```text
POST https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction
```

Request:

```json
{
  "RefNum": "...",
  "TerminalNumber": 123456
}
```

---

# وضعیت‌های پیشنهادی

رکورد Payment می‌تواند این وضعیت‌ها را داشته باشد:

```text
pending
 token_created
 callback_received
 paid
 failed
 verify_failed
 reversed
```

---

# کدهای Verify / Reverse

| کد | معنی |
|---:|---|
| `0` | موفق |
| `2` | درخواست تکراری |
| `-2` | تراکنش یافت نشد |
| `-6` | بیش از نیم ساعت از تراکنش گذشته است |
| `-104` | ترمینال غیرفعال است |
| `-105` | ترمینال وجود ندارد |
| `-106` | IP مجاز نیست |
| `5` | تراکنش قبلاً برگشت خورده است |

---

# Token

Request:

```http
POST https://sep.shaparak.ir/onlinepg/onlinepg
Content-Type: application/json
```

نمونه:

```json
{
  "Action": "Token",
  "TerminalId": "123456",
  "Amount": 15000000,
  "ResNum": "ORDER-1001",
  "RedirectUrl": "https://example.com/payment/sep/callback",
  "CellNumber": "09120000000"
}
```

پاسخ موفق:

```json
{
  "status": 1,
  "token": "..."
}
```

پاسخ خطا:

```json
{
  "status": -1,
  "errorCode": "5",
  "errorDesc": "..."
}
```

Token به طور پیش‌فرض 20 دقیقه معتبر است و مقدار قابل تنظیم آن طبق مستند SEP بین 20 تا 3600 دقیقه clamp می‌شود.

---

# ResNum

`ResNum` شناسه خرید سمت فروشنده است و باید برای هر تراکنش یکتا باشد.

حداکثر طول مستندشده: 50 کاراکتر.

مثال خوب:

```text
ORDER-20261004-1001
```

---

# RefNum

`RefNum` رسید دیجیتال صادرشده توسط SEP است و در سطح سیستم Unique است.

اما نکته مهم این است که **مصرف‌شدن آن را SEP برای شما به عنوان state مصرف‌شده مدیریت نمی‌کند**؛ برنامه پذیرنده باید مشخص کند که RefNum قبلاً در کدام Payment استفاده شده است.

---

# HashedCardNumber

این قابلیت برای پذیرندگان خاص است و باید مجوز SEP را داشته باشد.

مستند SEP ورودی این پارامتر را MD5 و خروجی `HashedCardNumber/HashedPan` را SHA-256 توصیف می‌کند.

**هرگز PAN خام را در دیتابیس ذخیره نکنید.**

این پکیج عمداً هیچ متدی برای ذخیره PAN خام ارائه نمی‌دهد.

---

# IP Server

برای Token و Verify/Reverse، IP سرور پذیرنده باید مطابق اطلاعات ثبت‌شده نزد SEP باشد.

اگر سرور عوض شود یا IP Public تغییر کند، باید اطلاعات پذیرنده نزد SEP نیز به‌روزرسانی شود.

---

# Middleware و CSRF

Route داخلی پکیج به صورت پیش‌فرض **هیچ middlewareای ندارد** تا Callback مستقیم SEP با CSRF/session guard مسدود نشود.

اگر می‌خواهید middleware اضافه کنید، از `config/sep.php` مقدار `middleware` را تنظیم کنید؛ اما Callback را با `auth` یا session login محافظت نکنید و CSRF را برای آن route اعمال نکنید.

در صورت استفاده از route اختصاصی خودتان، همین اصل را رعایت کنید.

---

# سفارشی‌سازی Repository

اگر Order/Payment سیستم خودتان را دارید، لازم نیست از جدول `sep_payments` استفاده کنید.

Interface:

```php
Vestra\Sep\Contracts\PaymentRepository
```

را implement کنید و binding آن را در Service Container جایگزین کنید.

این کار برای WooCommerce، فروشگاه اختصاصی، SaaS و ERPها مناسب است.

---

# تست

```bash
composer install
composer test
```

تحلیل استاتیک:

```bash
composer analyse
```

یا:

```bash
composer check
```

---

# Production Checklist

- [ ] HTTPS فعال است.
- [ ] TerminalId صحیح است.
- [ ] Public IP سرور نزد SEP ثبت شده است.
- [ ] RedirectURL دقیقاً در محیط Production قابل دسترسی است.
- [ ] Callback بدون authentication به آن endpoint می‌رسد.
- [ ] CSRF برای Callback به صورت صحیح مدیریت شده است.
- [ ] مبلغ با integer ریالی ارسال می‌شود.
- [ ] ResNum برای هر پرداخت یکتا است.
- [ ] RefNum ذخیره و Unique شده است.
- [ ] فقط Verify موفق با مبلغ صحیح باعث paid شدن Order می‌شود.
- [ ] Timeout Verify با Retry کنترل می‌شود.
- [ ] پاسخ خطادار Verify بی‌جهت Retry نمی‌شود.
- [ ] PAN/CVV2/PIN2 ذخیره نمی‌شود.
- [ ] لاگ‌ها اطلاعات حساس کارت را ثبت نمی‌کنند.
- [ ] Duplicate Callback باعث تحویل دوباره نمی‌شود.
- [ ] Reverse برای سناریوهای لازم پیاده‌سازی شده است.
- [ ] دسترسی به TerminalId و endpoint configuration محدود است.

---

# مستند رسمی SEP

این پکیج بر اساس مستند فنی SEP نسخه 3.6 موجود در مجموعه مستندات رسمی مورد استفاده در این پروژه نوشته شده است.

آدرس‌های اصلی مستند:

- Token: `https://sep.shaparak.ir/onlinepg/onlinepg`
- Payment: `https://sep.shaparak.ir/OnlinePG/OnlinePG`
- SendToken: `https://sep.shaparak.ir/OnlinePG/SendToken`
- Verify: `https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction`
- Reverse: `https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction`
- Reports: `https://report.sep.ir/`

---

# License

MIT
