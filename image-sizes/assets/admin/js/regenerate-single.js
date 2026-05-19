(function () {
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('#thumbpress-regenerate-image');
		if (!btn) return;

		e.preventDefault();

		var imageId = btn.getAttribute('data-image_id');
		if (!imageId) return;

		var originalHTML = btn.innerHTML;
		btn.innerHTML = '<b>Regenerating...</b>';
		btn.disabled = true;

		fetch(THUMBPRESS.api_base + '/regenerate/single', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': THUMBPRESS.nonce,
			},
			body: JSON.stringify({ image_id: parseInt(imageId, 10) }),
		})
			.then(function (res) { return res.json(); })
			.then(function (res) {
				if (res.success) {
					thumbpressToast(true, 'Thumbnails regenerated successfully!', 'success');
					setTimeout(function () { location.reload(); }, 1500);
				} else {
					btn.innerHTML = originalHTML;
					btn.disabled = false;
					thumbpressToast(true, res.data && res.data.message ? res.data.message : 'Failed to regenerate thumbnails.', 'error');
				}
			})
			.catch(function () {
				btn.innerHTML = originalHTML;
				btn.disabled = false;
				thumbpressToast(true, 'An error occurred while regenerating thumbnails.', 'error');
			});
	});
})();
