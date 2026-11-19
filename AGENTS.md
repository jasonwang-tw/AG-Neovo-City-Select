# AG Neovo City Select 維護規範

- 修改前讀 README.md、PLAN.md、DOC.md；正式站操作另讀主題 AGENTS.md，保留既有未提交修改。
- 已確認範圍為台灣傳統結帳、會員地址、購物車運費計算及 SAP 地址正規化。維持現有外觀、縣市 key、欄位名稱、3 碼帶入及人工較精確郵遞區號。
- 不批次修改會員地址、歷史訂單或 metadata，不改配送區／離島排除、金流、發票、退貨流程。不得建立訂單、管理員工作階段或重送 SAP 來驗證。
- RY 啟用時前端待命；不要同時掛載兩套欄位渲染器與城市 JS。WooCommerce 缺少時安全降級。
- SAP 正規化必須只讀本地獨立資料，不依賴 RY 的函式、檔案或 gettext domain；未知內容與原有中文值不猜改。
- 初次載入／AJAX 更新不得清空或覆寫既有地址。只有使用者明確更改相應縣市／鄉鎮才重設下游選項與預設郵遞區號；不得擅自選第一筆。
- 僅處理自身 address 欄位，保留必填、描述、autocomplete、readonly／disabled 與自訂屬性；不要破壞 Checkout Field Editor 或綠界選店流程。
- 使用 WordPress／WooCommerce 標準 Hook 與 API，class／函式／handle 使用 agneovo 名稱空間。前端只在需要的 WooCommerce 頁面載入；沿用現有 SelectWoo，不新增框架、全域 CSS 或無意義動畫。
- 複製／衍生的 RY 台灣資料保留來源及 GPLv3 授權；不要靜默更新字典，先比對 key、名稱與郵遞區號。
- PHP 與 JS 語法、全量字典對照、WordPress Hook、SAP、前端互動及桌面／手機驗證完成後再評估切換；未經實際登入會員操作不可稱作真實結帳驗收。
- Git commit 使用繁體中文；提交／推送只能使用 GitHub App connector。使用者未要求 push 時不提交或推送，不猜測儲存庫位置。


## 儲存庫與推送規範

- 本專案儲存庫：`https://github.com/jasonwang-tw/AG-Neovo-City-Select.git`，預設分支 `main`。不得提交至 AGNeovo_display 或其他專案。
- 使用者於 2026-11-19 已授權將 1.0.0 及 1.1.0 推送至上述儲存庫；之後的推送仍依當次授權範圍處理。
- 所有遠端提交／推送／發布只能使用 GitHub App connector；不得使用 `git add`、`git commit`、`git push`、GitHub CLI 或直接 HTTP 寫入作為替代。connector 無法完成時立即停止並回報具體原因。
- 除非使用者明確要求另建分支，直接使用預設 main。提交訊息一律使用繁體中文。
- 推送完成需同時滿足遠端成功、遠端 SHA 核對、本地安全同步；先比對本地檔案與遠端 tree。若有不一致、額外未追蹤檔案或可能覆寫使用者變更，停止並回報，不使用 `reset --hard` 或清除變更。
- 驗證日誌、備份、測試截圖、訂單／會員資料及環境秘密不得納入此儲存庫。SAP 是另一個外掛，本儲存庫僅提供地址正規化 API 及整合說明。


## 1.1.0 後台管理頁

- 使用者已於 2026-11-19 確認加入先前建議的精簡管理頁：運作狀態、套用範圍、地址資料、地址測試與操作說明。沿用 WordPress 原生後台樣式。
- 入口為 WooCommerce → City Select，限制 `manage_woocommerce`；WooCommerce 缺少時使用「設定」入口及 `manage_options` 安全降級。
- 管理頁與測試工具唯讀。不得新增儲存設定、會員／訂單寫入、外部 API、物流／金流切換或假裝可儲存的按鈕。
- 原生 form-table／notice／button 足夠；不加入額外 UI Kit、React、Vue、SelectWoo 或自訂全域 CSS。管理頁 JS 只在該頁載入。
- 地址測試使用本地字典及獨立正規化結果；不得觸發 SAP、Email 或結帳流程。顯示文字必須轉義，DOM 輸出使用 textContent。
- 不建立管理員 session 或登入 Cookie；以單次程序的受限權限模擬和隔離瀏覽器表單驗證，不得宣稱已驗收正式後台登入流程。
- 使用者已另行授權推送 1.1.0；限本專案 main，以 GitHub App connector 提交，核對遠端後安全同步非公開本地工作副本。
