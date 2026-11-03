# Easy ContentPush

Push WordPress content from staging to production in one click — including ACF fields, media, taxonomies, SEO metadata, and more.

![Stable Version](https://img.shields.io/badge/version-1.4.0-blue.svg)
![WordPress Tested Up To](https://img.shields.io/badge/tested%20up%20to-7.1-green.svg)
![License](https://img.shields.io/badge/license-GPLv2%2B-lightgrey.svg)

---

## ✨ Features

- **Push content** button in the block editor for posts, pages & custom post types
- Transfers:
  - ACF Flexible Content, Repeaters, Relationships, Groups
  - Featured images & media (no duplication)
  - Custom taxonomies & terms
  - Yoast SEO metadata
- Respects post status (draft, scheduled, published), templates, and hierarchies
- Customizable settings for source/target URLs and allowed post types

---

## ⚙️ How It Works

When editing a post, page, or custom post type, a **Push content** button appears in the sidebar. Clicking it sends the post and all associated data — including ACF fields, taxonomy, media, SEO metadata, and scheduled status — to your configured target site via a signed REST API.

---

## 🚀 Getting Started

1. Install and activate **Easy ContentPush** on both your **staging** and **production** sites.
2. On your **staging site**:
   - Go to `Settings → Easy ContentPush`
   - Set the **Target Site URL** (your live site)
   - Select which post types to enable
3. On your **production site**:
   - Go to `Settings → Easy ContentPush`
   - Set the **Origin Site URL** (your dev site)
4. Edit a post and click **Push content** in the editor sidebar.

> ✅ Make sure both sites have the same post types, taxonomies, and ACF field groups defined.

---

## ❓ FAQ

**Does it sync content both ways?**  
No — it's a one-way push from staging/dev to production.

**Do both sites need the plugin?**  
Yes. It must be installed and active on both sites.

**Does it support scheduled or draft posts?**  
Yes. Post status and publish dates are preserved.

**Is authentication needed?**  
Only the specified source URL can send data to the live site.

**Does it duplicate media?**  
No. It checks for existing media by filename.

---

## 🛠 Support & Contributions

Have questions, need help, or want to contribute?

- Email: [anandhu.natesh@gmail.com](mailto:anandhu.natesh@gmail.com)
- GitHub: [github.com/Macromaniak/easy-content-push](https://github.com/Macromaniak/easy-content-push)

---

## 📄 License

GPLv2 or later — [Full License](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)