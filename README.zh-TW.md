# Polylang Elementor Archive Bridge

Polylang Elementor Archive Bridge 讓一個 Elementor Pro 彙整頁範本的分類法條件，可以套用到所有由 Polylang 明確連結的翻譯詞彙。當多個語言要共用同一套彙整頁設計、又不想為每個翻譯分類或標籤各存一條條件時，才需要這個外掛。

這是獨立開發的相容性外掛，並非 Elementor 或 Polylang 的官方產品，也未獲其背書。

## 解決的具體問題

Elementor Pro Theme Builder 透過顯示條件選擇範本。分類法條件會指定特定詞彙，而 Polylang 會把各語言翻譯保存為不同 ID 的詞彙。這個外掛會在已儲存條件的詞彙，與目前詞彙、父詞彙或祖先詞彙屬於同一個 Polylang 翻譯群組時，將對應 ID 交回 Elementor，再由 Elementor 完成原生條件判斷。

適合使用的情況：

- 已啟用 Elementor Pro 與 Polylang。
- 多個語言的分類法彙整頁要共用同一個範本設計。
- 該分類法同時由 Elementor 條件與 Polylang 管理。
- 各語言詞彙已在 Polylang 正確設定語言並互相連結。

不適合使用的情況：

- 每個語言需要不同的 Elementor 範本設計。
- 條件是頁面、文章、作者、日期、搜尋結果或其他非分類法情境。
- 詞彙尚未指定語言或尚未連結為翻譯。
- 需要語言切換器、動態標籤、內容翻譯或範本複製功能。

## 核心功能

- 支援精確詞彙條件。
- 支援直接子詞彙與任意後代詞彙條件。
- 保留 Elementor 原生的 Include 與 Exclude 行為。
- 支援分類、標籤，以及同時受兩個產品管理的公開自訂分類法。
- 在單次 request 內快取候選詞彙與翻譯群組。
- 提供預設關閉的 Conditions Cache Protection，處理特定 Theme Builder cache 重建症狀。
- 提供預設關閉的 Nested Loop Conditions Save Protection，處理
  `Edit Loop Template` → `Save & Back` 的文件切換競態。
- 修正 Elementor Pro 在 ACF 預覽讀值時，把目前分類詞彙物件轉成裸數字的問題。
- 不修改已儲存的 Elementor 條件，也不改動前台彙整頁查詢。

## 運作方式

假設 Elementor 條件儲存英文分類 ID `10`，而 Polylang 將它與繁中分類 ID `20` 連結。在繁中彙整頁上，外掛會辨識這個翻譯關係，並在 Elementor 評估條件時提供相關的目前詞彙 ID；最後的 Include／Exclude 判斷仍由 Elementor 執行。

對直接子詞彙與任意後代詞彙條件，外掛會用同樣方式比對目前詞彙的父層或祖先鏈。

taxonomy Archive 透過 Template widget 載入含有 ACF Dynamic Tag 的 Saved
Template 時，Elementor Pro 可能在預覽 request 把正確的 `WP_Term` 轉成裸數字，
導致 ACF 誤認為 Post ID。外掛只會把這個完全符合目前 queried term 的錯誤值
修正為 `term_{ID}`；其他 object ID 全部維持原值。

## 安裝方式

1. 從目標 GitHub Release 下載附加的 ZIP。
2. 在 WordPress 前往「外掛 > 安裝外掛 > 上傳外掛」。
3. 上傳 ZIP、安裝並啟用。
4. 確認 Elementor Pro 與 Polylang 已啟用。

若要手動安裝，請把 `polylang-elementor-archive-bridge` 目錄複製到 `wp-content/plugins/`。

## 操作方式

1. 在 Polylang 為分類法詞彙指定語言，並連結各語言翻譯。
2. 建立或編輯一個 Elementor Pro 彙整頁範本。
3. 使用主要語言的詞彙新增分類法顯示條件，例如：

   ```text
   Include > Categories > Product
   ```

4. 不要為同一翻譯群組的其他語言另加重複條件。
5. 發布範本，並逐一測試各語言的分類法彙整頁。

### Archive Saved Template 內的 ACF 欄位

ACF 值必須儲存在 taxonomy term。Elementor Template widget 載入含 ACF
Dynamic Tag 的 Saved Template 時，Archive term identity 修正會自動生效，
不需要另外啟用設定。本功能不會把獨立 WordPress Page 的 post meta 映射到 term。

## 選用防護功能

兩項防護預設都關閉，而且負責不同問題。

### Conditions Cache Protection

儲存一個 Theme Builder Template 後，如果其他 Polylang 後台語言的 Templates
從 Elementor condition 結果消失，才啟用此功能。它會讓 Elementor 重建全站
Theme Builder Conditions cache 時載入所有語言。

若 cache 已經不完整，啟用後需重新儲存任一 Theme Builder Display Conditions
一次。沒有語言過濾造成的 cache 症狀時請保持關閉。

### Nested Loop Conditions Save Protection

只有符合以下完整流程時才啟用：

1. 在 Elementor 開啟外層 Section、Page 或 Archive Template。
2. 從其中的 Loop Grid 點擊 **Edit Template**。
3. 編輯 Loop Item 後點擊 **Save & Back**。
4. 回到外層 Template 後，原有 Display Conditions 消失。

Loop Item 本身不支援 Theme Builder Display Conditions，但巢狀文件切換時，
空的 Loop Conditions request 可能留在 Elementor 的延遲 AJAX queue，之後
誤用外層 Template ID。此功能會阻止該無效 request 進入 queue，不會阻止
Loop Item 內容儲存，也不會攔截外層 Template 合法的 Conditions 變更。

啟用前已經刪除的 Conditions 無法從空值推測還原，必須重新建立一次。

## 限制與不會執行的功能

- 必須有 Elementor Pro 與 Polylang；本外掛不取代任何一方。
- 只有已連結的詞彙翻譯會匹配；缺少或無關的翻譯會維持原狀。
- 同一詞彙翻譯群組若建立多個語言專用範本，除非條件互斥，否則可能重疊匹配。
- 建議翻譯詞彙維持對應階層，但階層並不是翻譯匹配鍵。
- 整合依賴 Elementor Pro 的 filter hooks；未來 Elementor 若變更 hook，可能需要更新本外掛。
- Conditions Cache Protection 是依照所述症狀提供的選用 workaround，不代表上游已確認存在 bug。
- Nested Loop 防護依賴 Elementor 目前的 `loop-item` document type 與
  `theme_builder_save_conditions` editor action 名稱；遇到未知未來 API 時會
  fail-open。
- 不會翻譯、建立、複製或同步 Elementor 範本。
- 不會寫入 `_elementor_conditions` metadata。
- 不會修改 WordPress 彙整頁查詢、詞彙內容、網址、slug 或語言關係。
- 不會新增 widget、動態標籤或語言切換器。
- 啟用時不會執行 migration 或自動修復。
- ACF 修正只接受原始與 queried object 為同一個 `WP_Term`，且前一個 filter
  回傳相同裸數字的情況。`null`、`WP_Term`、`term_{ID}`、Loop、Options、User、
  Comment 與其他正確 context 全部原樣回傳。

## 系統需求

- WordPress 6.5 或以上
- PHP 7.4 或以上
- 具備 Theme Builder 的 Elementor Pro
- Polylang
- 使用 Archive term identity 修正時需要 Advanced Custom Fields

## 官方資料

- [Elementor：建立或修改彙整頁範本](https://elementor.com/help/archive-site-part/)
- [Elementor 開發者文件：Theme Conditions](https://developers.elementor.com/docs/theme-conditions/)
- [Polylang 開發者文件：Function reference](https://polylang.pro/documentation/support/developers/function-reference/)
- [WordPress Plugin Handbook：外掛標頭規格](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)

## 開發與驗證

從 repository 根目錄執行獨立 smoke tests：

```bash
php tests/run.php
node tests/nested-loop-conditions-save-protection.test.js
node tests/plugin-contract.test.js
```

執行 PHP 語法檢查：

```bash
php -l polylang-elementor-archive-bridge/polylang-elementor-archive-bridge.php
php -l tests/run.php
```

`tests/` 只保留在 source，不會放入 WordPress 安裝 ZIP。

## 最新版本

`1.4.0` 修正 Elementor Archive 預覽中的 ACF taxonomy term identity，同時保留
Loop context，且上游已回傳正確 object ID 時會自動旁路。

## 授權

GPL-2.0-or-later。
