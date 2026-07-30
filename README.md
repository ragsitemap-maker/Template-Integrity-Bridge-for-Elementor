# Polylang Elementor Archive Bridge

Polylang Elementor Archive Bridge lets one Elementor Pro archive template taxonomy condition match every translated term explicitly linked by Polylang. It is useful when the same archive design should serve several languages without saving a separate condition for every translated category, tag, or supported custom-taxonomy term.

This is an independent compatibility plugin. It is not affiliated with or endorsed by Elementor or Polylang.

## The problem it solves

Elementor Pro Theme Builder selects templates through display conditions. A taxonomy condition identifies a particular term, while Polylang represents translations as distinct terms with distinct IDs. This plugin maps the saved condition term to the current term, parent, or ancestor when both belong to the same Polylang translation group, and then leaves the final condition decision to Elementor.

The plugin is appropriate when:

- Elementor Pro and Polylang are active.
- One archive template design should be shared by translated taxonomy archives.
- The taxonomy is managed by both Elementor's conditions and Polylang.
- The translated terms are correctly linked in Polylang.

It is not appropriate when:

- Each language needs a different Elementor template design.
- The condition targets a page, post, author, date, search result, or another non-taxonomy context.
- Terms have not been assigned a language or linked as translations.
- You need a language switcher, dynamic tags, content translation, or template duplication.

## Core features

- Supports exact taxonomy term conditions.
- Supports direct-child and any-descendant taxonomy conditions.
- Preserves Elementor's include and exclude behavior.
- Supports categories, tags, and public custom taxonomies handled by both products.
- Caches term candidates and translation groups in memory for the current request.
- Offers disabled-by-default Conditions Cache Protection for a specific Theme Builder cache-rebuild symptom.
- Offers disabled-by-default Nested Loop Conditions Save Protection for the
  `Edit Loop Template` → `Save & Back` document-switch race.
- Does not modify saved Elementor conditions or frontend archive queries.

## How it works

Assume an Elementor condition is saved for English category ID `10`, and Polylang links it to Traditional Chinese category ID `20`. On the translated archive, the plugin recognizes the linked translation and supplies the relevant current term ID while Elementor evaluates the condition. Elementor still performs its native include/exclude check.

For direct-child and any-descendant conditions, the bridge applies the same translation-group comparison to the current term's parent or ancestor chain.

## Installation

1. Download the ZIP attached to the desired GitHub Release.
2. In WordPress, go to **Plugins > Add New > Upload Plugin**.
3. Upload the ZIP, install it, and activate it.
4. Confirm that Elementor Pro and Polylang are active.

For a manual installation, copy the `polylang-elementor-archive-bridge` directory to `wp-content/plugins/`.

## Usage

1. Assign languages to the taxonomy terms and link their translations in Polylang.
2. Create or edit one Elementor Pro Archive Template.
3. Add a taxonomy Display Condition using the term in the primary language, for example:

   ```text
   Include > Categories > Product
   ```

4. Do not add separate conditions for translations in the same term group.
5. Publish the template and test each translated taxonomy archive.

## Optional protections

Both protections are disabled by default and have separate responsibilities.

### Conditions Cache Protection

Enable this when saving one Theme Builder Template causes Templates from other
Polylang admin languages to disappear from Elementor's condition results. It
makes Elementor include every language when rebuilding the global Theme Builder
conditions cache.

After enabling it, re-save any Theme Builder Display Conditions once to rebuild
an already incomplete cache. Leave it disabled if this language-filtered cache
symptom is absent.

### Nested Loop Conditions Save Protection

Enable this when all of the following happen:

1. You open an outer Section, Page, or Archive Template in Elementor.
2. From a Loop Grid inside it, you click **Edit Template**.
3. You edit the Loop Item and click **Save & Back**.
4. The outer Template's Display Conditions disappear.

Elementor Loop Items do not support Theme Builder Display Conditions. During the
nested document switch, however, an empty Loop Conditions request can remain in
Elementor's delayed AJAX queue and later use the outer Template ID. This guard
prevents that invalid request from entering the queue. It does not block the
Loop Item content save or legitimate Display Conditions changes made on the
outer Template.

Conditions deleted before this protection was enabled cannot be inferred from
an empty value. Recreate them once after enabling the setting.

## Limitations and non-features

- Elementor Pro and Polylang are required; this plugin does not replace either product.
- Only linked term translations can match. Missing or unrelated translations are left unchanged.
- Separate language-specific templates for the same term group can produce overlapping matches unless their conditions are mutually exclusive.
- Matching term hierarchy is recommended but is not the key used for translation matching.
- The integration depends on Elementor Pro filter hooks. A future Elementor change to those hooks may require a plugin update.
- Conditions Cache Protection is an opt-in workaround based on the described symptom. It is not a claim of a confirmed upstream bug.
- Nested Loop protection depends on Elementor's current `loop-item` document
  type and `theme_builder_save_conditions` editor action names. Unknown future
  APIs fail open.
- The plugin does not translate, create, duplicate, or synchronize templates.
- The plugin does not write `_elementor_conditions` metadata.
- The plugin does not alter WordPress archive queries, term content, URLs, slugs, or language relationships.
- The plugin does not add widgets, dynamic tags, or a language switcher.
- No migration layer or automatic repair runs on activation.

## Requirements

- WordPress 6.5 or later
- PHP 7.4 or later
- Elementor Pro with Theme Builder
- Polylang

## Official references

- [Elementor: Create or modify archive templates](https://elementor.com/help/archive-site-part/)
- [Elementor developer documentation: Theme Conditions](https://developers.elementor.com/docs/theme-conditions/)
- [Polylang developer documentation: Function reference](https://polylang.pro/documentation/support/developers/function-reference/)
- [WordPress Plugin Handbook: Header requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)

## Development and validation

Run the isolated smoke tests from the repository root:

```bash
php tests/run.php
node tests/nested-loop-conditions-save-protection.test.js
node tests/plugin-contract.test.js
```

Run PHP syntax checks:

```bash
php -l polylang-elementor-archive-bridge/polylang-elementor-archive-bridge.php
php -l tests/run.php
```

The `tests/` directory is source-only and is intentionally excluded from the installable ZIP.

## Latest release

Version `1.3.0` adds the optional Nested Loop Conditions Save Protection and
keeps all existing archive translation and cache-protection behavior
independent.

## License

GPL-2.0-or-later.
