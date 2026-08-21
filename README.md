# Template Integrity Bridge for Elementor

Template Integrity Bridge for Elementor is a narrowly scoped compatibility plugin for Elementor Template integrity.

Protects Elementor template selection, render context, condition cache, and nested editor state, including Polylang-aware taxonomy matching.

Formerly Polylang Elementor Archive Bridge.

Template Integrity Bridge is an independent plugin. It is not affiliated with or endorsed by Elementor or Polylang.

## What the plugin protects

| Integrity area | Feature | Default | Dependency |
| --- | --- | --- | --- |
| Template Selection Integrity | Polylang taxonomy condition mapping | Always active | Elementor Pro and Polylang |
| Template Selection Integrity | Conditions Cache Protection | Disabled | Elementor Pro and Polylang |
| Template Render Context Integrity | Archive ACF term correction | Enabled | Elementor Pro and ACF |
| Template Editor State Integrity | Nested Loop Conditions Save Protection | Disabled | Elementor Pro |

The four features solve separate problems. Enabling one setting does not enable or alter another feature.

## Template Selection Integrity

### Polylang taxonomy condition mapping

Elementor stores a taxonomy Display Condition using one term ID, while Polylang stores each translation as a different term ID. During an Archive request, the plugin checks whether the saved term and the current term, parent, or ancestor belong to the same explicit Polylang translation group. When they do, it supplies the relevant ID and leaves the final Include or Exclude decision to Elementor.

This always-active mapping supports:

- Exact taxonomy terms.
- Direct Child conditions.
- Any Child or descendant conditions.
- Include and Exclude.
- Categories, tags, and public custom taxonomies managed by both Elementor and Polylang.

Translation groups and ancestor candidates are cached only for the current request. The plugin does not duplicate conditions, change `_elementor_conditions`, or modify the frontend query.

### Conditions Cache Protection

Enable this only when saving one Theme Builder Template causes Templates from other Polylang admin languages to disappear from Elementor's condition results. It adds `lang=''` only to Elementor's dedicated conditions-cache rebuild query.

This protection is disabled by default. After enabling it, re-save one Theme Builder Display Condition to rebuild an already incomplete cache. Leave it disabled when this symptom is absent.

## Template Render Context Integrity

### Archive ACF term correction

This enabled-by-default correction has one precise target:

```text
Theme Builder taxonomy Archive
└─ Elementor Template widget
   └─ directly selected Loop Item Template
      └─ ACF Dynamic Tag should read the queried taxonomy term
```

In this structure, Elementor can identify the loaded document as `loop-item` even though no post Loop iteration is running. Its ACF provider then uses `get_the_ID()`, so ACF looks at the current post instead of the queried Archive term and may display the field's default value.

The plugin changes the ACF identity only when all confirmed runtime conditions match. The upstream value must still be unresolved, and the Template widget's selected Template ID must exactly equal the current Loop Item document ID. Only then does it supply `term_{ID}` for the queried taxonomy term.

The correction does not target:

- A normal Loop Grid iterating posts.
- A Taxonomy Loop.
- A Loop Item whose current document ID differs from the Template widget selection.
- Options, User, Comment, post, or page ACF data.
- Non-taxonomy Archives or non-Template widgets.
- Mapping ACF values from a WordPress Page to a taxonomy term.

Store the ACF value on the taxonomy term. The correction changes only the object identity used for that lookup; it does not edit the field, default value, Template, query, or saved data.

### Compatibility with a future Elementor Pro fix

The correction checks the live runtime shape rather than an Elementor version list. If Elementor Pro or ACF already returns a non-null value, stops using the current post ID, changes the document type, no longer exposes the expected API, or no longer produces an exact Template/document ID match, the plugin returns the upstream value unchanged.

Unexpected API types and exceptions also fail open without warnings, notices, log output, or automatic setting changes. The checkbox may remain enabled; the correction silently becomes a no-op when the exact failure signature is absent.

## Template Editor State Integrity

### Nested Loop Conditions Save Protection

Enable this only when the following sequence removes the outer Template's Display Conditions:

1. Open an outer Section, Page, or Archive Template in Elementor.
2. From a Loop Grid, choose **Edit Template**.
3. Edit the Loop Item and choose **Save & Back**.
4. The outer Template's Display Conditions disappear.

This protection is disabled by default. It prevents an invalid empty Loop Conditions request from entering Elementor's delayed AJAX queue. It does not block the Loop Item content save or legitimate condition changes on the outer Template. Conditions deleted before the protection was enabled must be recreated once.

The guard depends on Elementor's current editor action names and fails open for unknown future APIs.

## Installation and settings

1. Upload the release ZIP in **Plugins > Add New > Upload Plugin** and activate it.
2. Confirm that Elementor Pro is active, plus the dependency required by each feature.
3. Open **Settings > Template Integrity**.
4. Keep **Archive ACF term correction** enabled only for the exact Archive → Template widget → Loop Item structure described above.
5. Enable either disabled-by-default protection only when its matching symptom is present.

For manual installation, copy the `polylang-elementor-archive-bridge` directory to `wp-content/plugins/`.

## Upgrade identity

Version 1.5.0 uses the external package name `template-integrity-bridge-for-elementor-1.5.0.zip`, but the ZIP intentionally keeps the internal `polylang-elementor-archive-bridge/` folder and main PHP filename. The settings slug and existing option keys also remain unchanged. This lets WordPress replace the existing plugin instead of installing a second plugin and preserves all saved settings without a migration.

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

## Module dependencies

- **Elementor Pro:** common platform for all four features.
- **Polylang:** required by taxonomy condition mapping and Conditions Cache Protection.
- **Advanced Custom Fields:** required only by Archive ACF term correction.
- **Nested Loop Conditions Save Protection:** does not depend on Polylang or ACF.

## Boundaries

- Only terms explicitly linked by Polylang can match.
- The plugin does not translate, create, duplicate, or synchronize Templates.
- It does not add widgets, Dynamic Tags, a language switcher, CSS tools, performance tools, or a general query builder.
- It does not alter term content, URLs, slugs, hierarchy, language relationships, or WordPress queries.
- It does not map WordPress Page ACF data to taxonomy term data.
- Conditions Cache Protection is a symptom-specific workaround, not a claim of a confirmed upstream bug.
- No activation migration, background task, diagnostic log, telemetry, remote request, or automatic data repair is performed.

## Requirements

- WordPress 6.5 or later
- PHP 7.4 or later
- Elementor Pro with Theme Builder
- Polylang for the two Polylang-aware selection/cache features
- Advanced Custom Fields for Archive ACF term correction

## Development and validation

Run from the repository root:

```bash
php -l polylang-elementor-archive-bridge/polylang-elementor-archive-bridge.php
php -l tests/run.php
php -l tests/fail-open-without-elementor.php
php tests/run.php
php tests/fail-open-without-elementor.php
node tests/nested-loop-conditions-save-protection.test.js
node tests/plugin-contract.test.js
```

The `tests/`, `diagnostics/`, and `local-docs/` directories are source-only and are not included in the installable ZIP.

## Latest release

Version `1.5.0` introduces the Template Integrity Bridge public brand and reorganizes the documentation around Template Selection, Render Context, and Editor State Integrity. WordPress technical identity, stored settings, protection defaults, hooks, matching rules, and runtime behavior remain unchanged from 1.4.4.

## Official references

- [Elementor: Create or modify archive templates](https://elementor.com/help/archive-site-part/)
- [Elementor developer documentation: Theme Conditions](https://developers.elementor.com/docs/theme-conditions/)
- [Polylang developer documentation: Function reference](https://polylang.pro/documentation/support/developers/function-reference/)
- [WordPress Plugin Handbook: Header requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)

## License

GPL-2.0-or-later.