# Polylang Elementor Archive Bridge

Polylang Elementor Archive Bridge 是針對 Elementor Pro、Polylang 與 taxonomy Archive 的小範圍相容性外掛。它讓多語分類頁共用同一套 Archive 設計，並分別處理三種 Elementor 工作流程問題。

這是獨立開發的外掛，並非 Elementor 或 Polylang 的官方產品，也未獲其背書。

## 先確認你遇到哪一種問題

| 問題 | 對應功能 | 預設狀態 |
| --- | --- | --- |
| 一條 Elementor taxonomy Display Condition 無法套用到 Polylang 已連結的翻譯詞彙 | Polylang taxonomy condition mapping | 固定啟用 |
| Archive 的 Template widget 直接嵌入 Loop Item Template，裡面的 ACF 讀到目前文章或欄位預設值，而不是 Archive term | Archive ACF term correction | 開啟 |
| 儲存 Theme Builder Conditions 後，其他 Polylang 後台語言的 Templates 從 Elementor condition 結果消失 | Conditions Cache Protection | 關閉 |
| `Edit Loop Template` → `Save & Back` 後，外層 Template 的 Display Conditions 消失 | Nested Loop Conditions Save Protection | 關閉 |

這四列是不同問題。啟用其中一項防護，不會連動或改寫其他功能。

## 1. 讓 Polylang 翻譯詞彙共用一個 Archive Condition

這是外掛的核心功能，沒有開關。

Elementor 會用一個 term ID 儲存 taxonomy Display Condition；Polylang 則把每個語言的翻譯保存成不同 term ID。Elementor 評估 Archive request 時，外掛會確認已儲存的詞彙，是否與目前詞彙、父詞彙或祖先詞彙屬於同一個 Polylang 翻譯群組。符合時只提供對應 ID，最後的 Include／Exclude 判斷仍由 Elementor 執行。

支援範圍：

- 精確 taxonomy term。
- Direct Child。
- Any Child 或任意後代。
- Include 與 Exclude。
- 分類、標籤，以及同時受 Elementor 與 Polylang 管理的公開自訂 taxonomy。

外掛不會複製 Display Conditions、不會修改 `_elementor_conditions`，也不會改動前台 query。

## 2. Template widget 直接嵌入 Loop Item Template 時的 ACF

這項修正只處理一個精確結構：

```text
Theme Builder taxonomy Archive
└─ Elementor Template widget
   └─ 直接選取一份 Loop Item Template
      └─ ACF Dynamic Tag 應讀取目前 queried taxonomy term
```

在這個結構中，雖然當下沒有執行文章 Loop 疊代，Elementor 仍可能把載入中的文件判定為 `loop-item`。它的 ACF provider 因而使用 `get_the_ID()`，導致 ACF 查詢目前文章，而不是 queried Archive term，最後可能顯示欄位的預設值。

**Archive ACF term correction** 開啟時，外掛只會在已由實際執行紀錄確認的全部條件同時成立時介入，其中包括：上游尚未回傳值，而且 Template widget 選取的 Template ID 必須完全等於目前 Loop Item document ID。符合時才把 ACF object identity 改成 queried taxonomy term 的 `term_{ID}`。

這項修正不處理：

- 正常執行文章疊代的 Loop Grid。
- Taxonomy Loop。
- 目前 document ID 與 Template widget 選取 ID 不同的 Loop Item。
- Options、User、Comment、post 或 page ACF 資料。
- 非 taxonomy Archive 或非 Template widget。
- 把 WordPress Page 的 ACF 值映射到 taxonomy term。

ACF 值必須儲存在 taxonomy term。本功能只修正該次 ACF 查詢使用的 object identity，不會修改欄位、預設值、Template 或 query。

### 未來 Elementor Pro 修正後會怎樣？

本功能不使用 Elementor 版本清單，而是檢查當下的 runtime 輸入。

如果 Elementor Pro 或 ACF 已回傳非 `null` 的正確值、不再使用目前文章 ID、改變 document type、不再提供預期的 document API，或 Template ID 與 document ID 不再完全相同，外掛會原樣保留上游結果。遇到異常 API 型別或例外時也會靜默放行，不輸出 warning、notice、log，也不會自動改寫設定。

因此 checkbox 可以維持勾選；只要精確的錯誤形狀已不存在，修正分支就會自然成為 silent no-op，不會與未來的 Elementor 修正衝突。

## 3. 兩項選用防護

以下兩項防護互相獨立，而且預設關閉。

### Conditions Cache Protection

只有在儲存一個 Theme Builder Template 後，其他 Polylang 後台語言的 Templates 從 Elementor condition 結果消失時才啟用。它只會在 Elementor 專用的 Conditions cache rebuild query 加入 `lang=''`。

若 cache 已經不完整，啟用後需重新儲存一次 Theme Builder Display Condition。沒有這項症狀時保持關閉。

### Nested Loop Conditions Save Protection

只有以下流程會刪除外層 Template 的 Display Conditions 時才啟用：

1. 在 Elementor 開啟外層 Section、Page 或 Archive Template。
2. 從 Loop Grid 選擇 **Edit Template**。
3. 編輯 Loop Item 後選擇 **Save & Back**。
4. 外層 Template 的 Display Conditions 消失。

此防護會阻止無效的空白 Loop Conditions request 進入 Elementor 延遲 AJAX queue，不會阻止 Loop Item 內容儲存，也不會攔截外層 Template 的合法 Conditions 變更。啟用前已刪除的 Conditions 必須重建一次。

## 安裝與設定

1. 在「外掛 > 安裝外掛 > 上傳外掛」上傳 release ZIP，安裝並啟用。
2. 確認 Elementor Pro 與 Polylang 已啟用。
3. 前往「設定 > Archive Bridge」。
4. 使用前述 `Archive → Template widget → Loop Item Template` 精確結構時，維持 **Archive ACF term correction** 開啟；未使用該結構或需要停止修正時可取消勾選。
5. 另外兩項 protection 只有在對應症狀存在時才啟用。

手動安裝時，將 `polylang-elementor-archive-bridge` 目錄複製到 `wp-content/plugins/`。

## 建立一個共用的 Archive Condition

1. 在 Polylang 為每個 taxonomy term 指定語言，並明確連結各語言翻譯。
2. 建立或編輯一個 Elementor Pro Archive Template。
3. 使用主要語言的詞彙儲存一條 taxonomy Display Condition，例如：

   ```text
   Include > Categories > Product
   ```

4. 不要為同一個翻譯群組的其他語言加入重複條件。
5. 發布後逐一測試每個語言的 taxonomy Archive。

只有在不同語言的 Templates 使用互斥 Display Conditions 時，才建立語言專用 Template。

## 功能邊界

- 必須有 Elementor Pro 與 Polylang；只有使用 ACF 修正時才需要 Advanced Custom Fields。
- 只有 Polylang 明確連結的翻譯詞彙可以互相匹配。
- 外掛不會翻譯、建立、複製或同步 Templates。
- 不會新增 widget、Dynamic Tag 或語言切換器。
- 不會修改詞彙內容、網址、slug、階層、語言關係或 WordPress query。
- Cache Protection 是針對特定症狀的 workaround，不代表上游已確認存在 bug。
- Nested Loop 防護依賴 Elementor 目前的 editor action 名稱，遇到未知未來 API 時會 fail-open。
- 不會執行 activation migration、背景工作、診斷紀錄或自動資料修復。

## 系統需求

- WordPress 6.5 或以上
- PHP 7.4 或以上
- 具備 Theme Builder 的 Elementor Pro
- Polylang
- 使用 Archive ACF term correction 時需要 Advanced Custom Fields

## 開發與驗證

從 repository 根目錄執行：

```bash
php -l polylang-elementor-archive-bridge/polylang-elementor-archive-bridge.php
php -l tests/run.php
php tests/run.php
php tests/fail-open-without-elementor.php
node tests/nested-loop-conditions-save-protection.test.js
node tests/plugin-contract.test.js
```

`tests/`、`diagnostics/` 與 `local-docs/` 只存在於 source，不會放入 WordPress 安裝 ZIP。

## 最新版本

`1.4.4` 新增預設開啟且可獨立取消的 Archive ACF term correction 設定，縮短非候選 ACF 路徑，並在上游行為已正確或 Elementor API 形狀未知時靜默放行。1.4.3 已由執行紀錄確認的匹配規則保持不變。

## 官方資料

- [Elementor：建立或修改彙整頁範本](https://elementor.com/help/archive-site-part/)
- [Elementor 開發者文件：Theme Conditions](https://developers.elementor.com/docs/theme-conditions/)
- [Polylang 開發者文件：Function reference](https://polylang.pro/documentation/support/developers/function-reference/)
- [WordPress Plugin Handbook：外掛標頭規格](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)

## 授權

GPL-2.0-or-later。