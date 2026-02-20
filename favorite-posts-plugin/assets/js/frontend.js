/* global fpFavoriteConfig */
(function () {
	'use strict';

	if (!window.fpFavoriteConfig || !window.fetch) {
		return;
	}

	var locks = new Map();
	var debounceDelay = 300;

	function setButtonState(button, isSaved) {
		button.classList.toggle('is-favorited', isSaved);
		button.classList.toggle('is-saved', isSaved);
		button.classList.toggle('bg-black', isSaved);
		button.classList.toggle('bg-[#007AFF]', !isSaved);
		button.setAttribute('aria-pressed', String(isSaved));
		var label = button.querySelector('.fp-label');
		if (label) {
			label.textContent = isSaved ? fpFavoriteConfig.i18n.saved : fpFavoriteConfig.i18n.save;
		}
	}

	function setLoading(button, loading) {
		button.classList.toggle('is-loading', loading);
		button.classList.toggle('opacity-75', loading);
		button.classList.toggle('pointer-events-none', loading);
		button.disabled = loading;
	}

	function parseResponse(response) {
		if (!response.ok) {
			return response.json().then(function (payload) {
				var message = payload && payload.message ? payload.message : fpFavoriteConfig.i18n.error;
				throw new Error(message);
			});
		}
		return response.json();
	}

	function toggleFavorite(button) {
		var postId = Number(button.getAttribute('data-post-id'));
		if (!postId) {
			return;
		}

		var currentlySaved = button.classList.contains('is-favorited');
		setButtonState(button, !currentlySaved);
		setLoading(button, true);

		fetch(fpFavoriteConfig.endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': fpFavoriteConfig.nonce
			},
			body: JSON.stringify({ post_id: postId })
		})
			.then(parseResponse)
			.then(function (payload) {
				if (!payload || !payload.success) {
					throw new Error(fpFavoriteConfig.i18n.error);
				}
				setButtonState(button, payload.status === 'added');
			})
			.catch(function (error) {
				setButtonState(button, currentlySaved);
				button.classList.add('has-error');
				window.setTimeout(function () {
					button.classList.remove('has-error');
				}, 1000);
				window.console.error(error.message);
			})
			.finally(function () {
				setLoading(button, false);
			});
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest('.fp-favorite-btn');
		if (!button) {
			return;
		}

		event.preventDefault();
		var key = button.getAttribute('data-post-id');

		if (locks.has(key)) {
			window.clearTimeout(locks.get(key));
		}

		var timeout = window.setTimeout(function () {
			locks.delete(key);
			toggleFavorite(button);
		}, debounceDelay);

		locks.set(key, timeout);
	});
})();
