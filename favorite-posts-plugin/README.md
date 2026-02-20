# Favorite Posts Performance Plugin

A lightweight WordPress plugin that adds a high-performance **Save/Saved** bookmark button for posts.

## Features

- REST endpoint: `POST /wp-json/sharea/v1/favorite`
- Toggle bookmarks (add/remove) for authenticated users
- Redis-friendly object cache layer for user favorites
- Optimistic UI frontend with debounced click handling
- Dark/light mode compatible styles
- i18n-ready strings with `favorite-posts-plugin` text domain
- PHPUnit coverage for endpoint auth, nonce, and toggle flow

## Installation

1. Copy the `favorite-posts-plugin` directory into `wp-content/plugins/`.
2. Activate **Favorite Posts Performance Plugin** from wp-admin.
3. Ensure your object cache/Redis integration is enabled for best performance.
4. Add buttons where needed, for example:

```html
<button class="fp-favorite-btn" data-post-id="123" aria-pressed="false">
  <span class="fp-label">Save</span>
</button>
```

## Usage

- Logged-in users can click the button to save/remove favorites.
- The plugin JS sends a POST request with nonce security.
- Responses include:

```json
{
  "success": true,
  "status": "added",
  "message": "Bookmark successful"
}
```

## API Details

- **Route:** `/wp-json/sharea/v1/favorite`
- **Method:** `POST`
- **Body:** `{ "post_id": 123 }`
- **Headers:** `X-WP-Nonce: <wp_rest_nonce>`

## Uninstall Cleanup

On uninstall, the plugin:

- Drops the `{prefix}favorites` table.
- Clears cached favorite sets for users.

## Testing

- PHP test file included at `tests/test-rest-api.php`.
- Run with WordPress PHPUnit test scaffold.

## FAQ

### Does this work for guests?
No. Guests receive a `401` response for favorite requests.

### Is Redis required?
No, but strongly recommended. The plugin uses WordPress object cache APIs so Redis object cache plugins work out of the box.
