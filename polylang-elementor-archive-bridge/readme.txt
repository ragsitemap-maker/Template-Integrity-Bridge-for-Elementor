=== Polylang Elementor Archive Bridge ===
Contributors: site-development-team
Tags: elementor, polylang, archive, theme builder, display conditions
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Use one Elementor Pro Archive Template taxonomy condition for all term
translations explicitly linked by Polylang.

== Description ==

This independent compatibility bridge is useful when several languages share
one archive design. Elementor Pro and Polylang are required. It does not
translate Elementor templates, add widgets, alter frontend queries, duplicate
Display Conditions, or write Elementor metadata.

When Elementor evaluates a taxonomy archive condition, the plugin:

1. Reads the term ID saved in the Elementor Display Condition.
2. Builds the candidate term set required by Elementor's Exact, Direct Child,
   or Any Child condition.
3. Gets candidate Polylang translation groups with
   pll_get_term_translations().
4. Gives Elementor the matching current term, parent, or ancestor ID and lets
   Elementor perform its normal condition check.

The taxonomy mapping uses one frontend filter. Translation groups are cached in
memory for the current request.

An optional, disabled-by-default Conditions Cache Protection setting adds
`lang=''` only to Elementor's dedicated Theme Builder conditions cache rebuild
query. Use this workaround only for the documented incomplete-cache symptom; it
is not a claim of a confirmed upstream bug.

An independent, disabled-by-default Nested Loop Conditions Save Protection
setting prevents a Loop Item opened through a Loop Grid inside another Template
from queueing an empty Theme Builder Conditions request during Save & Back. It
does not block the Loop Item content save.

Elementor Pro can turn a queried `WP_Term` into a bare numeric ID while ACF
loads values in a Theme Builder preview. ACF then treats the number as a post
ID and falls back to the field default. This plugin corrects only that exact
current-archive-term identity to `term_{ID}`. Correct object IDs, Loop items,
Options, Users, Comments, and future fixed Elementor output pass through.

== Installation ==

1. Upload the `polylang-elementor-archive-bridge` folder to
   `/wp-content/plugins/`, or install the ZIP in Plugins > Add New > Upload Plugin.
2. Activate Polylang, Elementor Pro, and this plugin.
3. Create one Elementor Archive Template.
4. Add a Display Condition for the taxonomy term in the main language.
5. Do not add separate conditions for translations of the same term.
6. Optional: open Settings > Archive Bridge and enable Conditions Cache
   Protection if saving one Theme Builder template causes other templates to
   disappear from Elementor's conditions cache.
7. Optional: enable Nested Loop Conditions Save Protection if editing a Loop
   Item from inside another Template causes the outer Template's Display
   Conditions to disappear after Save & Back.
8. When a Saved Template inside a taxonomy Archive uses an ACF Dynamic Tag,
   store the value on the taxonomy term. The term identity correction is
   automatic and has no setting.

Example:

    Include > Categories > Product (English term ID 10)

If Polylang links term 10 to Chinese term 20, German term 30, and Japanese term
40, the same Elementor template is selected on all four category archives.

== Scope ==

Supported:

* Categories
* Tags
* Public custom taxonomies handled by both Elementor and Polylang
* Exact taxonomy term conditions
* Direct Child taxonomy term conditions
* Any Child taxonomy term conditions
* Include and Exclude conditions
* One unchanged Elementor Template ID across languages
* Optional all-language Elementor conditions cache regeneration
* Optional nested Loop Item conditions-save race protection
* ACF term identity correction for taxonomy Archive previews

Not included:

* Elementor template translation
* Language-switcher widgets or dynamic tags
* Page, post, author, date, search, or other non-taxonomy conditions
* Automatic repair of missing or incorrectly linked Polylang term translations
* Changes to term content, URLs, slugs, hierarchy, or language relationships
* A migration layer or activation-time automatic repair
* Mapping WordPress Page post meta to taxonomy term meta

Limitations:

* Elementor Pro and Polylang are required.
* Only terms explicitly linked by Polylang can match.
* Separate templates for the same translation group can overlap.
* The bridge depends on Elementor Pro filter hooks that may change in a future release.
* Nested Loop protection depends on Elementor's current `loop-item` document type
  and `theme_builder_save_conditions` editor action names.
* The ACF correction applies only when the original object and queried object
  are the same `WP_Term` and an earlier filter returns the same bare numeric ID.
  Correct upstream output is returned unchanged.

== Frequently Asked Questions ==

= Does this copy conditions into Elementor metadata? =

No. The saved `_elementor_conditions` value remains unchanged. Mapping happens
only while Elementor evaluates the current request.

= Must term slugs and hierarchy be identical? =

No. The bridge uses Polylang's explicit translation relationship. Matching
hierarchy is still recommended for site structure, but it is not the matching
key.

= Can I also create separate language-specific templates? =

Not for the same translated term group unless their conditions are deliberately
made mutually exclusive. Otherwise multiple templates can match at the same
priority. This plugin is intended for one shared template.

= What happens if a term translation is missing? =

Only linked terms match. Unrelated or missing translations do not inherit the
condition.

= Is Conditions Cache Protection enabled automatically? =

No. It is disabled by default. Enable it explicitly in Settings > Archive
Bridge only if saving one Theme Builder template's Display Conditions causes
other templates' conditions to disappear or become incomplete. After enabling
it, re-save any Theme Builder Display Conditions once to rebuild the cache.

= Is Nested Loop Conditions Save Protection enabled automatically? =

No. It is disabled by default. Enable it only if Edit Loop Template followed by
Save & Back causes the outer Template's Display Conditions to disappear. The
guard runs only in the Elementor editor and does not block Loop content saves.
Conditions already deleted before enabling it must be recreated once.

= Does the ACF Archive term correction need to be enabled? =

No. It has no setting and runs only when every strict condition matches the
confirmed `WP_Term` to bare-number failure. If Elementor Pro returns `null`, a
`WP_Term`, `term_{ID}`, or another valid object ID, the plugin returns it
unchanged. ACF values must be stored on the taxonomy term, not on a separate
WordPress Page.

== Changelog ==

= 1.4.0 =

* Correct the current taxonomy Archive term identity for ACF in Elementor previews.
* Preserve Post Loop, Taxonomy Loop, Options, User, Comment, and non-Archive contexts.
* Automatically pass through correct output after an upstream Elementor Pro fix.

= 1.3.0 =

* Add disabled-by-default Nested Loop Conditions Save Protection.
* Prevent Loop Items from queueing an invalid empty Theme Builder Conditions request.
* Keep Loop content saves and legitimate outer Template Conditions saves unchanged.
* Separate nested editor race protection from Polylang cache rebuild protection.

= 1.2.2 =

* Rewrite the Conditions Cache Protection setting around the visible failure symptom.
* Explain when to enable it, what it changes, and how to rebuild an already incomplete cache.

= 1.2.1 =

* Use the general Polylang language API as the Conditions Cache Protection capability boundary.
* Keep taxonomy translation API usage isolated to runtime term mapping.
* Preserve cache behavior and avoid calling the language-list API.

= 1.2.0 =

* Add an admin settings page under Settings > Archive Bridge.
* Add optional Conditions Cache Protection for Elementor cache regeneration.
* Keep the new feature disabled by default.
* Preserve all existing taxonomy mapping behavior independently of the setting.

= 1.1.0 =

* Add Direct Child taxonomy condition translation.
* Add Any Child taxonomy condition translation.
* Add request-local candidate and translation-group caches.
* Add malformed hierarchy cycle protection.

= 1.0.0 =

* Initial release.
