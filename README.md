# Polylang Elementor Archive Bridge

Polylang Elementor Archive Bridge is a narrowly scoped compatibility plugin for Elementor Pro, Polylang, and taxonomy Archives. It keeps one Archive design usable across translated taxonomy terms and includes separate safeguards for three Elementor workflows.

This is an independent plugin. It is not affiliated with or endorsed by Elementor or Polylang.

## Start here: which problem do you have?

| Problem | Feature | Default |
| --- | --- | --- |
| One Elementor taxonomy Display Condition does not match the linked Polylang translations | Polylang taxonomy condition mapping | Always active |
| ACF inside a Loop Item Template directly embedded by a Template widget reads the current post or the ACF default instead of the Archive term | Archive ACF term correction | Enabled |
| Saving Theme Builder conditions causes Templates from another Polylang admin language to disappear from Elementor's condition results | Conditions Cache Protection | Disabled |
| `Edit Loop Template` → `Save & Back` removes the outer Template's Display Conditions | Nested Loop Conditions Save Protection | Disabled |

The four rows describe different problems. Enabling one protection does not enable or alter another feature.

## 1. Shared Archive conditions across Polylang translations

This is the plugin's core function and has no switch.

Elementor stores a taxonomy Display Condition using one term ID. Polylang stores each translation as a different term ID. When Elementor evaluates an Archive request, the bridge checks whether the saved term and the current term, parent, or ancestor belong to the same explicit Polylang translation group. If they do, it supplies the relevant ID and leaves the final Include or Exclude decision to Elementor.

Supported condition types:

- Exact taxonomy term.
- Direct Child.
- Any Child or descendant.
- Include and Exclude.
- Categories, tags, and public custom taxonomies managed by both Elementor and Polylang.

The plugin does not duplicate conditions, change `_elementor_conditions`, or modify the frontend query.

## 2. ACF in a directly embedded Loop Item Template

This correction has one precise target:

```text
Theme Builder taxonomy Archive
└─ Elementor Template widget
   └─ directly selected Loop Item Template
      └─ ACF Dynamic Tag should read the queried taxonomy term
```

In this structure, Elementor can identify the loaded document as `loop-item` even though no post Loop iteration is running. Its ACF provider then uses `get_the_ID()`, so ACF looks at the current post instead of the queried Archive term and may display the field's default value.

When **Archive ACF term correction** is enabled, the plugin changes the ACF identity only when all confirmed runtime conditions match, including an unresolved upstream value and an exact match between the Template widget's selected Template ID and the current Loop Item document ID. It then supplies `term_{ID}` for the queried taxonomy term.

This correction does not target:

- A normal Loop Grid iterating posts.
- A Taxonomy Loop.
- A Loop Item whose current document ID differs from the Template widget selection.
- Options, User, Comment, post, or page ACF data.
- Non-taxonomy Archives or non-Template widgets.
- Mapping ACF values from a WordPress Page to a taxonomy term.

Store the ACF value on the taxonomy term. The correction changes only the object identity used for that ACF lookup; it does not edit the field, default value, Template, or query.

### What happens after a future Elementor Pro fix?

The feature does not use an Elementor version list. It checks the live runtime shape instead.

If Elementor Pro or ACF already returns a non-null value, stops using the current post ID, changes the document type, no longer exposes the expected document API, or no longer produces an exact Template/document ID match, the plugin returns the upstream value unchanged. Unexpected API types and exceptions also fail open without warnings, notices, log output, or automatic setting changes.

The checkbox may remain enabled; the correction simply becomes a silent no-op when its exact failure signature is absent.

## 3. Optional protections

The following protections are independent and disabled by default.

### Conditions Cache Protection

Enable this only when saving one Theme Builder Template causes Templates from other Polylang admin languages to disappear from Elementor's condition results. It adds `lang=''` only to Elementor's dedicated conditions-cache rebuild query.

After enabling it, re-save a Theme Builder Display Condition once to rebuild an already incomplete cache. Leave it disabled when this symptom is absent.

### Nested Loop Conditions Save Protection

Enable this only when the following sequence removes the outer Template's Display Conditions:

1. Open an outer Section, Page, or Archive Template in Elementor.
2. From a Loop Grid, choose **Edit Template**.
3. Edit the Loop Item and choose **Save & Back**.
4. The outer Template's Display Conditions disappear.

The guard prevents an invalid empty Loop Conditions request from entering Elementor's delayed AJAX queue. It does not block the Loop Item content save or legitimate condition changes on the outer Template. Conditions deleted before the guard was enabled must be recreated once.

## Installation and settings

1. Upload the release ZIP in **Plugins > Add New > Upload Plugin** and activate it.
2. Confirm that Elementor Pro and Polylang are active.
3. Open **Settings > Archive Bridge**.
4. Keep **Archive ACF term correction** enabled when using the exact Archive → Template widget → Loop Item structure above. Disable it when that structure is not used or the correction must be stopped.
5. Enable either protection only when its matching symptom is present.

For manual installation, copy the `polylang-elementor-archive-bridge` directory to `wp-content/plugins/`.

## Creating one shared Archive condition

1. Assign a language to every taxonomy term and explicitly link its translations in Polylang.
2. Create or edit one Elementor Pro Archive Template.
3. Save one taxonomy Display Condition using the term in the primary language, for example:

   ```text
   Include > Categories > Product
   ```

4. Do not add duplicate conditions for linked translations in the same group.
5. Publish and test every translated taxonomy Archive.

Use separate language-specific Templates only when their Display Conditions are deliberately mutually exclusive.

## Boundaries

- Elementor Pro and Polylang are required; ACF is required only for the ACF correction.
- Only terms explicitly linked by Polylang can match.
- The plugin does not translate, create, duplicate, or synchronize Templates.
- It does not add widgets, Dynamic Tags, or a language switcher.
- It does not alter term content, URLs, slugs, hierarchy, language relationships, or WordPress queries.
- Cache Protection is a symptom-specific workaround, not a claim of a confirmed upstream bug.
- Nested Loop protection depends on Elementor's current editor action names and fails open for unknown future APIs.
- No activation migration, background task, diagnostic log, or automatic data repair is performed.

## Requirements

- WordPress 6.5 or later
- PHP 7.4 or later
- Elementor Pro with Theme Builder
- Polylang
- Advanced Custom Fields when using Archive ACF term correction

## Development and validation

Run from the repository root:

```bash
php -l polylang-elementor-archive-bridge/polylang-elementor-archive-bridge.php
php -l tests/run.php
php tests/run.php
php tests/fail-open-without-elementor.php
node tests/nested-loop-conditions-save-protection.test.js
node tests/plugin-contract.test.js
```

The `tests/`, `diagnostics/`, and `local-docs/` directories are source-only and are not included in the installable ZIP.

## Latest release

Version `1.4.4` adds the independently configurable, enabled-by-default Archive ACF term correction setting, shortens non-candidate ACF paths, and silently fails open when upstream behavior is already correct or Elementor exposes an unknown API shape. The runtime-confirmed matching rules from 1.4.3 remain unchanged.

## Official references

- [Elementor: Create or modify archive templates](https://elementor.com/help/archive-site-part/)
- [Elementor developer documentation: Theme Conditions](https://developers.elementor.com/docs/theme-conditions/)
- [Polylang developer documentation: Function reference](https://polylang.pro/documentation/support/developers/function-reference/)
- [WordPress Plugin Handbook: Header requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)

## License

GPL-2.0-or-later.