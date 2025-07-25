=== Easy ContentPush ===
Contributors: anandhunadesh, phaseswpdev
Tags: content-sync, acf, media, dev-to-live, migration
Requires at least: 6.3
Tested up to: 6.8
Stable tag: 1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Push posts, pages, custom content, ACF fields, media, taxonomies & SEO from staging to production with one click.

=== Description ===

**Easy StagePush Sender** lets you safely and easily migrate content — including ACF fields, media files, featured images, taxonomy terms, and SEO metadata — directly from your staging/dev site to your production site. 

A “Push to Live” button appears in the editor sidebar for all supported post types. When you click the button, the plugin instantly transfers the post, including all custom fields and media references, to your live site using a secure REST API endpoint.

This plugin must be installed on both your production site and dev site to push the content. You should configure the plugin settings from the dashboard by providing the 'Target Site URL' and 'Origin Site URL' in the corresponding fields.

**Important:** Your production site should have the same post types, taxonomies, and ACF field groups as your staging/dev site. This plugin does not register or sync post type or field definitions—it only pushes content and metadata.

=== Features ===

* Manual “Push to Live” button for posts, pages, and custom post types (CPTs)
* One-click transfer of all post content, including ACF Flexible Content, Relationships, Repeaters, and Groups
* Seamless handling of featured images and other media (no duplication)
* Taxonomy and term synchronization (including custom taxonomies)
* Yoast SEO metadata transfer
* Respects page templates and parent/child hierarchies
* Supports scheduled posts, files, SVGs, and more
* Customizable settings panel for site URL and allowed post types

=== Usage ===

1. **Install and activate** Easy ContentPush on your development/staging WordPress site.
2. Go to **Settings → Easy ContentPush** on your dev/staging site and provide the target website URL (where you want to push the content to) in the field named 'Target Site URL' in settings page.
3. Select the post types you want to enable for pushing
4. **Install and activate** Easy ContentPush on your live/production WordPress site
5. Go to **Settings → Easy ContentPush** on your live/production site and provide the origin website URL (where you want to receive content from - Your development/staging website) in the field named 'Origin Site URL' in settings page.
6. Edit a post, page, or custom post type in development/production website.
7. Click the **Push to Live** button in the editor’s sidebar meta box.
8. Your post’s content, ACF data, media references, taxonomy, and SEO metadata will be transferred to your live site instantly, preserving status (draft, scheduled, published, etc.).

**Note:** Make sure all ACF field groups, post types, and taxonomies exist on both sites for proper mapping.

=== Frequently Asked Questions ===

= Does this plugin sync content both ways? =

No. Content is pushed one way only: from your staging/dev site to your production site.

= Is the plugin required on both websites? =

Yes. Easy ContentPush must be active on your both development(source) and production(target) websites to send and accept posts.

= Does the “Push to Live” button work for scheduled posts and drafts? =

Yes! The current post status (draft, published, scheduled, etc.) and post date are included in the push, so scheduled posts will remain scheduled on production.

= Is authentication required? =

Currently, the REST endpoint is resdtricted to receive content only from specified source URL.

= Will this create duplicate media? =

No. The plugin references media files by URL, and the receiver checks for existing media by filename to avoid duplication.

=== Support ===

For support, questions, or feature requests, contact anandhu.natesh@gmail.com / anandhu.nadesh@gmail.com  
Contributions are welcome on [GitHub](https://github.com/Macromaniak/easy-content-push).

=== License ===

This plugin is licensed under GPLv2 or later. You are free to use, modify, and distribute this plugin under the terms of the license.
