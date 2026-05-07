(function () {
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('#thumbpress-convert-image-avif');
		if (!btn) return;

		e.preventDefault();

		var imageId = btn.getAttribute('data-image_id');
		if (!imageId) return;

		var originalText = btn.innerHTML;
		btn.innerHTML = '<b>Converting...</b>';
		btn.disabled = true;

		fetch(THUMBPRESS.api_base + '/convert-avif/single', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': THUMBPRESS.nonce,
			},
			body: JSON.stringify({ image_id: parseInt(imageId, 10) }),
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (res) {
				if (res.success) {
					location.reload();
				} else {
					btn.innerHTML = originalText;
					btn.disabled = false;
					alert(res.message || 'Failed to convert image to AVIF.');
				}
			})
			.catch(function () {
				btn.innerHTML = originalText;
				btn.disabled = false;
				alert('An error occurred while converting the image to AVIF.');
			});
	});
})();
