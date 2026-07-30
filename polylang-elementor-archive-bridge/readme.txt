=== Polylang Elementor Archive Bridge ===
Contributors: site-development-team
Tags: elementor, polylang, archive, theme builder, display conditions
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 1.2.2
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

Not included:

* Elementor template translation
* Language-switcher widgets or dynamic tags
* Page, post, author, date, search, or other non-taxonomy conditions
* Automatic repair of missing or incorrectly linked Polylang term translations
* Changes to term content, URLs, slugs, hierarchy, or language relationships
* A migration layer or activation-time automatic repair

Limitations:

* Elementor Pro and Polylang are required.
* Only terms explicitly linked by Polylang can match.
* Separate templates for the same translation group can overlap.
* The bridge depends on Elementor Pro filter hooks that may change in a future release.

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

== Changelog ==

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
