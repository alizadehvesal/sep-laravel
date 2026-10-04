<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><title>نتیجه پرداخت</title></head>
<body>
    @if($result->paid)
        <h1>پرداخت با موفقیت تأیید شد.</h1>
        <p>شماره پیگیری: {{ $result->verification?->detail?->straceNo ?? '---' }}</p>
        <p>شماره مرجع: {{ $result->verification?->detail?->rrn ?? '---' }}</p>
    @else
        <h1>پرداخت تأیید نشد.</h1>
        <p>{{ $result->message }}</p>
    @endif
</body>
</html>
