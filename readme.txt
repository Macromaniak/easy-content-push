=== Easy ContentPush ===
Contributors: anandhunadesh, phaseswpdev
Tags: content-sync, acf, media, dev-to-live, migration
Requires at least: 6.3
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Push posts, pages, custom content, ACF fields, media, taxonomies & SEO from staging to production with one click.

=== Description ===

**Easy ContentPush** lets you safely and easily migrate content — including ACF fields, media files, featured images, taxonomy terms, and SEO metadata — directly from your staging/dev site to your production site. 

A **Push content** button appears in the editor sidebar for all supported post types. When you click the button, the plugin instantly transfers the post, including all custom fields and media references, to your configured target site using a signed REST API request.

This plugin must be installed on every site that sends or receives content. Configure **Settings → Easy ContentPush** on each site: target URL and push key for sending, sender site URLs and receive key for receiving. Requests are authenticated with HMAC-SHA256 signed REST requests.

**Important:** Your production site should have the same post types, taxonomies, and ACF field groups as your staging/dev site. This plugin does not register or sync post type or field definitions—it only pushes content and metadata.

=== Features ===

* Manual **Push content** button for posts, pages, and custom post types (CPTs)
* One-click transfer of all post content, including ACF Flexible Content, Relationships, Repeaters, and Groups
* Seamless handling of featured images and other media (no duplication)
* Taxonomy and term synchronization (including custom taxonomies)
* Yoast SEO metadata transfer
* Respects page templates and parent/child hierarchies
* Supports scheduled posts, files, SVGs, and more
* Customizable settings panel for site URLs, sync keys, and allowed post types
* HMAC-signed REST API and connection test from the settings screen

=== Usage ===

1. **Install and activate** Easy ContentPush on each WordPress site in your workflow.
2. On a site that **receives** content: open **Settings → Easy ContentPush**, add **Sender site URLs**, click **Generate new key**, and copy the **Receive key**.
3. On a site that **sends** content: enter the **Target site URL**, paste the target’s **Receive key** as your **Push key**, choose post types, and use **Test connection**.
4. Edit a post and click **Push content** in the editor sidebar.
5. Content, ACF data, media references, taxonomies, and SEO metadata are transferred while preserving status (draft, scheduled, published, etc.).

For chains (site A → B → C), configure push and receive sections independently on each middle site. Multiple senders may use the same receive key on the receiver.

**Note:** Make sure all ACF field groups, post types, and taxonomies exist on both sites for proper mapping.

=== Frequently Asked Questions ===

= Does this plugin sync content both ways? =

Yes. You just need to configure the settings accordingly.

= Is the plugin required on both websites? =

Yes. Easy ContentPush must be active on your both development(source) and production(target) websites to send and accept posts.

= Does the Push content button work for scheduled posts and drafts? =

Yes! The current post status (draft, published, scheduled, etc.) and post date are included in the push, so scheduled posts will remain scheduled on production.

= Is authentication required? =

Yes. Incoming requests must match a listed sender site URL and a valid HMAC signature using the shared receive key. Configure keys in **Settings → Easy ContentPush** on each site.

= Will this create duplicate media? =

No. The plugin references media files by URL, and the receiver checks for existing media by filename to avoid duplication.

== Changelog ==

= 1.4.0 =
* Editor control label renamed to **Push content** (replacing Push to Live).
* Clearer settings labels: push/receive sections, sender site URLs, and receive key.
* HMAC-SHA256 authentication for sync REST endpoints.
* Multiple sender site URLs with a shared receive key; push key for outbound sync.
* Redesigned settings screen (push, accept, and content sections) with connection test.
* Legacy single Origin Site URL migrated to the allowed origins list on upgrade.

= 1.3.0 =
* WordPress 7.0 compatibility: block editor sidebar panel for Push content (iframe-safe).
* Bumped minimum PHP to 7.4.
* Fixed receiver post import error handling and input sanitization.
* Classic meta box retained as fallback with inline script for iframe contexts.

=== Support ===

For support, questions, or feature requests, contact anandhu.natesh@gmail.com / anandhu.nadesh@gmail.com  
Contributions are welcome on [GitHub](https://github.com/Macromaniak/easy-content-push).

=== License ===

This plugin is licensed under GPLv2 or later. You are free to use, modify, and distribute this plugin under the terms of the license.
