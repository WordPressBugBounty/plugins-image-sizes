(function () {
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('#thumbpress-convert-image');
		if (!btn) return;

		e.preventDefault();

		var imageId = btn.getAttribute('data-image_id');
		if (!imageId) return;

		var originalText = btn.innerHTML;
		btn.innerHTML = '<b>Converting...</b>';
		btn.disabled = true;

		fetch(THUMBPRESS.api_base + '/convert/single', {
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
					thumbpressToast(true, 'Image converted to WebP successfully!', 'success');
					setTimeout(function () { location.reload(); }, 1500);
				} else {
					btn.innerHTML = originalText;
					btn.disabled = false;
					thumbpressToast(true, res.message || 'Failed to convert image.', 'error');
				}
			})
			.catch(function () {
				btn.innerHTML = originalText;
				btn.disabled = false;
				thumbpressToast(true, 'An error occurred while converting the image.', 'error');
			});
	});
})();
