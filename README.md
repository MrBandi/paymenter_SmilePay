# Paymenter SmilePay (非官方)

## 支援付款方式
- 銀行轉帳 (ATM 虛擬帳號)
- 7-11 ibon 超商代碼
- 全家 FamiPort 超商代碼

## 安裝步驟

### 1. 下載模組

將此 SmilePay 模組下載並放置於您的 Paymenter 系統的擴充模組目錄中：

```bash
cd /path/to/paymenter/extensions/Gateways
git clone https://github.com/MrBandi/paymenter_SmilePay.git
```

或者也可以直接下載 ZIP 檔案並解壓縮至 `extensions/Gateways/SmilePay` 目錄。

### 2. 更新 Paymenter

在 Paymenter 主目錄下執行以下命令更新系統並清除緩存：

```bash
php artisan optimize:clear
php artisan migrate
```

### 3. 啟用模組

1. 登入 Paymenter 管理後台
2. 前往 `Extensions` > `Gateways`
3. 點選 `New gateway` 創建新的付款方式
4. **Gateway** 的地方找到 `SmilePay` 並輸入相關資料（見下方設定說明）
5. 點選 `Save` 啟用付款方式

## 設定參數

| 參數名稱 | 說明 |
|---------|-----|
| SmilePay Merchant ID (Dcvc) | 商家代號，請至 SmilePay 商家後台確認 |
| SmilePay Parameter Code (Rvg2c) | 參數碼，請至 SmilePay 商家後台確認 |
| SmilePay Verify Key | 檢查碼，請至 SmilePay 商家後台確認 |
| SmilePay 商家驗證參數 | 用於驗證回呼資料的 4 位數驗證參數，請設定在「基本資料管理」中 |
| 啟用除錯模式 | 啟用此選項將記錄更詳細的資訊以協助排解問題 |
| 測試模式 | 啟用此選項將使用 SmilePay 測試環境進行交易測試 |

## Webhook 設定

系統會自動設置回呼網址，但您需要確保以下網址可以被 SmilePay 系統正常訪問：

```
https://您的網站網址/extensions/smilepay/webhook
```

請注意，此網址應該是公開可訪問的，以便 SmilePay 能正確回傳付款結果。

## DeBug 功能

管理員可以訪問以下路徑查看 DeBug 日誌（需啟用除錯模式）：

```
/admin/extensions/smilepay/debug-logs
```
