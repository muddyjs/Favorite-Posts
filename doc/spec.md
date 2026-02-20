
# 📌 SPEC.md — Favorite Posts Plugin Development Specification

## 📘 1. Project Background & Objective

**Plugin Name:** Favorite Posts Performance Plugin  
**Objective:** Provide a performance-focused post bookmarking feature.  
**Primary Requirements:**

- Display a "Save/Unsaved" button on post lists and single post pages.
- Support bookmarking and unbookmarking posts.
- Use REST API to save the bookmark data.
- Use Redis caching to reduce database calls and enhance performance.
- Frontend uses native Fetch API + Optimistic UI.
- Follow WordPress plugin development best practices for stable production environment.

---

## 📁 2. Plugin File Structure

```
favorite-posts-plugin/
├── favorite-posts-plugin.php        # Main entry file
├── src/
│   ├── class-api.php                # REST API routes and handling logic
│   ├── class-db.php                 # Database operations
│   ├── class-redis.php              # Redis caching logic
│   └── class-utils.php              # Utility logic (e.g., nonce checks)
├── assets/
│   ├── js/frontend.js               # Frontend interaction logic
│   └── css/styles.css               # Styles
├── tests/                           # Test code (PHPUnit / JS testing)
├── README.md                        # Project documentation
├── uninstall.php                    # Uninstall hooks
└── LICENSE                         # License (GPLv2+)
```

---

## 🛠 3. Feature Modules Specification

### 🧠 3.1 Plugin Base Setup — `favorite-posts-plugin.php`

**Purpose:** Initialize the plugin, register necessary hooks, and load required files.

**Includes:**

- Plugin header comments (name, version, author, description, etc.)
- Register activation/deactivation/uninstall hooks
- Include dependency files:
  - class-api.php
  - class-db.php
  - class-redis.php
  - class-utils.php
- Enqueue frontend resources (CSS/JS)

---

### 🌐 3.2 REST API Design — `class-api.php`

**Endpoint:** `/wp-json/sharea/v1/favorite`  
**Request Method:** POST  
**Request Parameters:**
- `post_id` *(int)* — Required

**Behavior:**

- Check user login status
- Verify Nonce
- Call DB/Redis logic to save/remove bookmark
- Return JSON structure:

```json
{
  "success": true,
  "status": "added" | "removed",
  "message": "Bookmark successful"
}
```

**Permissions:**

- For unauthenticated users, return 401 or redirect to login.

---

### 🗄 3.3 Database Layer — `class-db.php`

**Purpose:**

- Manage persistent storage of the bookmarked data.
- Use WordPress `wpdb` for secure database access.
- Database table structure:

| Field       | Type       | Description        |
|-------------|------------|--------------------|
| id          | bigint     | Primary Key        |
| user_id     | bigint     | User ID            |
| post_id     | bigint     | Post ID            |
| created_at  | datetime   | Bookmark timestamp |

**Functions:**

- `get_favorites($user_id)`
- `add_favorite($user_id, $post_id)`
- `remove_favorite($user_id, $post_id)`
- `is_favorited($user_id, $post_id)`

---

### ⚡ 3.4 Redis Caching Layer — `class-redis.php`

**Purpose:**

- Speed up bookmark status queries.
- Cache key naming conventions:
  - `fp_favorites_user_{user_id}` → Stores all `post_id`s the user has bookmarked.
- Cache expiration strategies:
  - Set TTL or sync cache refresh when bookmark is updated.
  - Use Redis data for faster access rather than querying the database.

---

### 🧑‍💻 3.5 Utility Class — `class-utils.php`

**Purpose:**

- Handle Nonce generation and verification logic.
- Check if the user is logged in.
- Handle common error responses.

---

## 🎨 4. Frontend Specification — `assets/js/frontend.js`

**Requirements:**

- Use event delegation to handle clicks on the bookmark button.
- Use native Fetch API for making REST requests.
- Optimistic UI updates on button click.
- Prevent duplicate submissions (throttling/lock mechanism).
- Handle success and failure feedback.

**Button Style Class Convention:**

```html
<button class="fp-favorite-btn" data-post-id="123">
  <span class="fp-label">Save</span>
</button>
```

---

## 🧁 5. Style Specification — `assets/css/styles.css`

**Requirements:**

- Plugin styles should not conflict with the theme (use prefix `fp-`).
- Support both light and dark themes.
- Provide visual feedback for "Saved" vs. "Un-Saved" states.

---

## 🧪 6. Testing Specification

**PHPUnit Testing:**

- Test REST API responses
- Test DB operations
- Test permissions & Nonce verification

**JS Testing Recommendations:**

- Use Jest/Cypress for testing frontend logic.

---

## 🛡 Security Requirements

- Sanitize and validate all inputs (using `sanitize_*`, `absint()`, etc.)
- Escape all outputs using `esc_html()`, `esc_attr()`, etc.
- Use Nonce verification for REST API requests.
- Protect against CSRF and XSS attacks.

---

## 📦 Release Standards

1. Adhere to WordPress coding standards and plugin best practices.
2. Provide a comprehensive README.md file.
3. Support internationalization (i18n).
4. Avoid hardcoded URLs, paths, etc.
5. Provide a `readme.txt` for WordPress.org plugin repository.

---

## 📘 README.md Content Suggestions

- Plugin overview
- Feature list
- Installation instructions
- Usage instructions
- Interface screenshots
- FAQ section
- Donation/feedback links

---

### 📌 Summary

This `SPEC.md` provides a clear, structured specification for developing the Favorite Posts Plugin, including plugin structure, REST API design, data storage and caching, frontend interaction, styling, testing, security, and release requirements. By following these guidelines, the plugin should meet WordPress standards and be production-ready.

---
