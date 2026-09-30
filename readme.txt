=== ogmake OG Images ===
Contributors: ogmake
Tags: open graph, og:image, social, twitter card, seo
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatic og:image and twitter:image for posts, pages and custom post types, rendered by the ogmake service.

== Description ==

Support: support@ogmake.com. Source: https://github.com/ogmake/ogmake-wordpress


Adds a social preview image to every post, page and enabled custom post type, built from the post title, excerpt, author and your site name.

* Public URL mode (key id only) or API key mode (signed URLs; the signing secret stays on the server and is never printed in HTML).
* Per-post override meta box: turn off, pick a template, change the image title, preview.
* Works with Yoast SEO and Rank Math: their image wins unless you opt to override it.
* Optional: keep the post's featured image when it has one.
* Image URLs are cached in transients. All plugin data is removed on uninstall.

== External services ==

This plugin relies on the ogmake image rendering service (https://ogmake.com by default; the address is configurable in Settings, ogmake) to generate the social preview images.

What is sent, and when: the plugin adds an image URL to your page's `og:image` and `twitter:image` meta tags. When a visitor's browser, a social network or a search engine loads that URL, it requests the image from ogmake. The URL carries the post title (or your override), an excerpt of up to 200 characters, the author's display name, the publication date, your site name and the template name, so the image can be rendered. In API key mode the URL also carries a signature; the signing secret itself is never sent or printed. Password-protected posts send the title only. The plugin itself makes no HTTP requests from your server, and nothing is sent until a post is viewed.

An ogmake account and key are required: https://ogmake.com. Terms of service: https://ogmake.com/terms. Privacy policy: https://ogmake.com/privacy. The plugin itself makes no HTTP requests from your server. You need an ogmake account and key: https://ogmake.com. Terms: https://ogmake.com/terms. Privacy policy: https://ogmake.com/privacy.

== Installation ==

1. Download the latest release zip from https://github.com/ogmake/ogmake-wordpress/releases, upload it under Plugins > Add New > Upload Plugin (or unzip into `/wp-content/plugins/`) and activate it.
2. Go to Settings, ogmake. Enter your key id (and signing secret for API key mode).
3. Choose a default template and post types.

== Frequently Asked Questions ==

= Where do colors, fonts and my logo come from? =

They are configured in your ogmake dashboard and apply to every image on your account.

= I already use Yoast SEO or Rank Math. =

Their og:image is kept when present. Tick "Override" in the settings to replace it.

= Does the plugin call ogmake from my server? =

No. Only the browsers and crawlers that load your pages request the image. See the External services section.

= What happens when I uninstall? =

All plugin settings, per-post overrides and cached URLs are removed.

== Changelog ==

= 0.1.0 =
* First release.

== Upgrade Notice ==

= 0.1.0 =
First release.
