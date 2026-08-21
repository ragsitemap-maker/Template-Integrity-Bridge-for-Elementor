=== Template Integrity Bridge for Elementor ===
Contributors: site-development-team
Tags: elementor, templates, theme builder, polylang, acf
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protects Elementor template selection, render context, condition cache, and nested editor state, including Polylang-aware taxonomy matching.

== Description ==

Template Integrity Bridge for Elementor is an independent compatibility plugin
for Elementor Template integrity. Formerly Polylang Elementor Archive Bridge.
It does not translate Templates, add widgets, alter frontend queries, duplicate
Display Conditions, or write Elementor metadata.

= Template Selection Integrity =

Polylang taxonomy condition mapping is always active. Elementor stores one term
ID in a taxonomy Display Condition, while Polylang stores every translation as a
different term ID. During an Archive request, the plugin verifies that the saved
term and current term, parent, or ancestor belong to the same explicit Polylang
translation group. It supplies the matching ID and leaves Elementor's native
Include or Exclude decision unchanged.

Supported modes are Exact, Direct Child, Any Child or descendant, Include, and
Exclude. Categories, tags, and public custom taxonomies managed by both
Elementor and Polylang are supported. Translation groups and hierarchy
candidates are cached only for the current request.

Conditions Cache Protection is an independent, disabled-by-default option. It
adds `lang=''` only to Elementor's dedicated conditions-cache rebuild query.
Enable it only when saving one Theme Builder Template causes Templates from
other Polylang admin languages to disappear. Re-save one Display Condition
after enabling it to rebuild an already incomplete cache.

= Template Render Context Integrity =

Archive ACF term correction is enabled by default and targets only this exact
structure:

    Theme Builder taxonomy Archive
    └─ Elementor Template widget
       └─ directly selected Loop Item Template
          └─ ACF Dynamic Tag should read the queried taxonomy term

Elementor can identify that directly loaded document as `loop-item` even when no
post Loop iteration is running. Its ACF provider can then use `get_the_ID()`, so
ACF reads the current post instead of the queried Archive term and may show the
field default.

The plugin changes the ACF identity only when all confirmed runtime conditions
match. The upstream value must remain unresolved, and the Template widget's
selected Template ID must exactly equal the current Loop Item document ID. Only
then does it supply `term_{ID}` for the queried taxonomy term.

It does not change a normal Loop Grid, Taxonomy Loop, a different Loop Item
document, Options, User, Comment, post, page, non-taxonomy Archive, or non-
Template-widget ACF lookup. ACF values must be stored on the taxonomy term; the
plugin does not map WordPress Page meta to a term.

If Elementor Pro or ACF already returns a non-null value, stops using the current
post ID, changes the document identity or API, or no longer produces an exact
Template/document ID match, the upstream value passes through unchanged.
Unexpected API types and exceptions also fail open without warnings, notices,
logs, or automatic setting changes. The enabled checkbox can therefore become
a silent no-op after an upstream fix.

= Template Editor State Integrity =

Nested Loop Conditions Save Protection is independent and disabled by default.
Enable it only when Edit Loop Template followed by Save & Back removes the outer
Template's Display Conditions.

The protection prevents an invalid empty Loop Conditions request from entering
Elementor's delayed AJAX queue. It does not block the Loop Item content save or
legitimate changes to the outer Template. Conditions deleted before the guard
was enabled must be recreated once. Unknown future editor APIs fail open.

= Module dependencies =

* Elementor Pro is the common platform for all four features.
* Polylang is required for taxonomy condition mapping and Conditions Cache
  Protection.
* Advanced Custom Fields is required only for Archive ACF term correction.
* Nested Loop Conditions Save Protection does not depend on Polylang or ACF.

== Installation ==

1. Upload the release ZIP in Plugins > Add New > Upload Plugin and activate it.
2. Confirm that Elementor Pro and the dependency required by each feature are
   active.
3. Open Settings > Template Integrity.
4. Keep Archive ACF term correction enabled only for the exact Archive >
   Template widget > Loop Item Template structure described above.
5. Enable either disabled-by-default protection only when its matching symptom
   is present.
6. For shared translated Archive conditions, assign a language to every term,
   link the translations in Polylang, and save one condition using the primary-
   language term.
7. Publish and test every translated taxonomy Archive.

Example:

    Include > Categories > Product (English term ID 10)

If Polylang links term 10 to Chinese term 20, German term 30, and Japanese term
40, the same Archive Template condition can match all four category Archives.

== Frequently Asked Questions ==

= Was this plugin renamed, and will an upgrade keep my settings? =

Yes. The public name is now Template Integrity Bridge for Elementor. Version
1.5.0 uses the external package filename
`template-integrity-bridge-for-elementor-1.5.0.zip`, while intentionally keeping
the internal `polylang-elementor-archive-bridge/` folder, main PHP filename,
settings slug, and existing option keys. WordPress therefore replaces the same
plugin and keeps all settings without a migration.

= Is the Polylang condition mapping configurable? =

No. It is always active. The three settings on Settings > Template Integrity
control only Archive ACF term correction and the two independent protections.

= Is Archive ACF term correction for Loop Grid? =

No. A normal Loop Grid iteration keeps its own post identity and passes through
unchanged. The correction is only for a Template widget that directly selects a
Loop Item Template on a taxonomy Archive, outside a real Loop iteration.

= Why does the ACF field show its default value? =

In the confirmed direct-embed structure, Elementor can give ACF the current post
ID instead of the queried taxonomy term. ACF cannot find the term's stored value
and may fall back to the field default. Store the value on the term and keep the
correction enabled only for that structure.

= Will this conflict after Elementor Pro fixes the problem? =

No conflicting override is applied when the exact failure signature is absent.
Correct non-null output, changed document identity, missing APIs, unexpected
types, and exceptions pass through silently. The plugin does not disable the
checkbox or overwrite data.

= Is Conditions Cache Protection enabled automatically? =

No. Enable it only when saving Theme Builder conditions causes Templates from
other Polylang admin languages to disappear. It is independent of taxonomy
matching and Archive ACF correction.

= Is Nested Loop Conditions Save Protection enabled automatically? =

No. Enable it only when Edit Loop Template followed by Save & Back removes the
outer Template's Display Conditions. It does not block Loop content saves.

= Does the plugin copy or repair Elementor conditions? =

No. `_elementor_conditions` remains unchanged. The plugin does not infer or
restore conditions that were already deleted.

== Scope ==

Supported:

* Linked Polylang taxonomy translations
* Exact, Direct Child, and Any Child taxonomy conditions
* Include and Exclude conditions
* Categories, tags, and supported public custom taxonomies
* Enabled-by-default Archive ACF term correction for the exact direct-embed path
* Optional all-language conditions-cache regeneration
* Optional nested Loop Item conditions-save race protection

Not included:

* Elementor Template translation, duplication, or synchronization
* Language-switcher widgets, Dynamic Tags, CSS tools, performance tools, or a
  general query builder
* Page, post, author, date, search, or other non-taxonomy condition mapping
* Mapping WordPress Page ACF data to taxonomy term data
* Changes to term content, URLs, slugs, hierarchy, language relationships, or
  WordPress queries
* Activation migrations, background jobs, diagnostics, telemetry, remote
  requests, or automatic data repair

Limitations:

* Elementor Pro is required.
* Polylang is required only for taxonomy mapping and Conditions Cache Protection.
* Advanced Custom Fields is required only for Archive ACF term correction.
* Only terms explicitly linked by Polylang can match.
* Separate Templates for the same translation group can overlap unless their
  conditions are mutually exclusive.
* Conditions Cache Protection is a symptom-specific workaround, not a claim of
  a confirmed upstream bug.
* Nested Loop protection depends on Elementor's current editor action names and
  fails open for unknown future APIs.

== Changelog ==

= 1.5.0 =

* Rename the public product to Template Integrity Bridge for Elementor.
* Reorganize documentation around Template Selection, Render Context, and Editor State Integrity.
* Preserve WordPress technical identity, stored settings, protection defaults, hooks, matching rules, and runtime behavior from 1.4.4.
= 1.4.4 =

* Add an enabled-by-default, independent Archive ACF term correction setting.
* Shorten non-candidate ACF paths without changing the confirmed matcher.
* Fail open silently for correct upstream values and unknown Elementor APIs.
* Reorganize documentation around the four distinct problems and settings.

= 1.4.3 =

* Target the runtime-confirmed directly embedded Loop Item ACF path.
* Require an exact selected-Template/current-document ID match.
* Preserve real nested Loops and all correct upstream output.

= 1.4.2 =

* Attempt to handle a frontend null-preload Saved Template ACF path.
* Keep the preview bare-ID correction and preserve unrelated contexts.
* Later runtime evidence showed this path was still incomplete.

= 1.4.1 =

* Attempt to capture the Archive term through a Saved Template preview query.
* Preserve Loop and non-Template contexts and pass through corrected output.
* Later runtime evidence disproved that preview-query hook as the frontend cause.

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