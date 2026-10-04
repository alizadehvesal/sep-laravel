# SEP 3.6 Mapping

| SEP | Package |
|---|---|
| Action | `TokenRequest::toArray()` = `Token` |
| Amount | `TokenRequest::$amount` |
| Wage | `TokenRequest::$wage` |
| TerminalId | config `sep.terminal_id` |
| ResNum | `TokenRequest::$resNum` |
| RedirectURL | `TokenRequest::$redirectUrl` |
| CellNumber | `TokenRequest::$cellNumber` |
| TokenExpiryInMin | `TokenRequest::$tokenExpiryInMin` |
| HashedCardNumber | `TokenRequest::$hashedCardNumber` |
| RefNum | `CallbackData::$refNum` / Verify request |
| State | `CallbackData::$state` |
| Status | `CallbackData::$status` |
| RRN | `CallbackData::$rrn` / `TransactionDetail::$rrn` |
| TraceNo | `CallbackData::$traceNo` / `TransactionDetail::$straceNo` |
| OrginalAmount | `TransactionDetail::$originalAmount` |
| AffectiveAmount | `TransactionDetail::$affectiveAmount` |
| Success | `TransactionResponse::$success` |
| ResultCode | `TransactionResponse::$resultCode` |
| ResultDescription | `TransactionResponse::$resultDescription` |
