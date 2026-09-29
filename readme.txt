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

= External service =

This plugin makes your visitors' browsers (and social networks) request images from the ogmake service (https://ogmake.com by default; configurable). The image URL contains the post title, excerpt (up to 200 characters), author name, date and site name so the image can be rendered. The plugin itself makes no HTTP requests from your server. You need an ogmake account and key: https://ogmake.com. Terms: https://ogmake.com/terms. Privacy policy: https://ogmake.com/privacy.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` and activate it.
2. Go to Settings, ogmake. Enter your key id (and signing secret for API key mode).
3. Choose a default template and post types.

== Frequently Asked Questions ==

= Where do colors, fonts and my logo come from? =

They are configured in your ogmake dashboard and apply to every image on your account.

= I already use Yoast SEO or Rank Math. =

Their og:image is kept when present. Tick "Override" in the settings to replace it.

== Changelog ==

= 0.1.0 =
* First release.
