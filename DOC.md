# 已確認需求與 UI 選型

## 使用者決策（2026-11-19）

1. 建立 AG Neovo City Select 及 README／AGENTS；使用者答覆「OK 開始開發」。
2. 範圍只含台灣，保留帳單／收件、會員地址及購物車運費計算；使用者另確認 checkout page 也要套用。
3. 維持 3 碼自動帶入、保留人工較精確的既有值；六碼門牌判定另列後續。
4. 沿用現有外觀與下拉搜尋，不重做版面。
5. 同步解除 SAP 對 RY 翻譯的相依；不改歷史訂單、會員地址、配送規則及退貨表單。

使用對象為現有台灣商城顧客，主要流程是填寫結帳／收件地址及維護會員地址；桌面及手機皆需可用。品牌色、字體、欄位尺寸與元件密度全部沿用現有主題，沒有新增風格選擇。

## GitHub 搜尋與比較

2026-11-19 以 WooCommerce classic checkout、jQuery、accessible select 搜尋 GitHub，並以 GitHub App 查詢儲存庫資料：

| 選項 | Stars／授權 | 優點 | 缺點及本案取捨 |
| --- | --- | --- | --- |
| [woocommerce/selectWoo](https://github.com/woocommerce/selectWoo) | 125／MIT；未封存，最近推送 2026-09-07 | WooCommerce 已載入、與既有 class／樣式及無障礙互動相容，免新增資源 | 獨立社群較小；跟隨 WooCommerce 的實際隨附版本，非另外下載新版 |
| [select2/select2](https://github.com/select2/select2) | 25,909／MIT；未封存，最近推送 2026-11-17 | jQuery 搜尋下拉、文件與社群完整，可客製外觀 | 另載入會增加體積及與 SelectWoo 共存風險，與已確認的「沿用原外觀」無額外收益 |

依使用者已選定的現有外觀與互動，沿用站上 WooCommerce 原生 SelectWoo，不新增其他 UI Kit 或自行設計下拉元件。沒有 React／Vue 實例或新 CSS 框架。

## Motion／效能

使用者選定保留既有流程。沿用元件本身的開啟、焦點及選項回饋，不新增長轉場、3D 或延遲結帳的動畫；沒有自訂動效，因此不增加減動效設定下的動畫。僅更新對應下游欄位，避免重複初始化 SelectWoo／事件與 AJAX 迴圈。

## 驗證範圍

Browser plugin not available，使用現有 Playwright／Chromium；不新增套件或建立管理員登入。測試採 WooCommerce 實際渲染的匿名範例表單，涵蓋帳單／收件、AJAX 重繪、手動郵遞區號及桌面／手機。這不等同已登入會員完成結帳或地址儲存；後者需使用者既有登入環境驗收。
