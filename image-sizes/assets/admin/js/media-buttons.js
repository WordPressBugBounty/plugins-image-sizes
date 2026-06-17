(function () {
	var modal    = document.getElementById( 'thumbpress-media-modal' );
	var overlay  = modal && modal.querySelector( '.thumbpress-media-overlay' );
	var closeBtn = modal && modal.querySelector( '.thumbpress-media-close' );
	var titleEl  = document.getElementById( 'thumbpress-media-title' );
	var descEl   = document.getElementById( 'thumbpress-media-desc' );
	var ctaEl    = document.getElementById( 'thumbpress-media-cta' );
	var ctaText  = document.getElementById( 'thumbpress-media-cta-text' );

	if ( ! modal || ! window.THUMBPRESS_MEDIA ) {
		return;
	}

	function openModal( feature ) {
		var data = THUMBPRESS_MEDIA.features[ feature ];
		if ( ! data ) {
			return;
		}

		titleEl.textContent = data.title;
		descEl.textContent  = data.description;
		ctaText.textContent = data.cta;
		ctaEl.href          = THUMBPRESS_MEDIA.upgrade_url;

		modal.style.display = 'flex';
		document.body.style.overflow = 'hidden';
	}

	function closeModal() {
		modal.style.display = 'none';
		document.body.style.overflow = '';
	}

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( 'button.thumbpress-media-btn' );
		if ( btn ) {
			e.preventDefault();
			openModal( btn.dataset.feature );
		}
	} );

	// Compress quality select — show upgrade prompt when user picks a value.
	document.addEventListener( 'change', function ( e ) {
		var sel = e.target.closest( 'select.thumbpress-media-btn' );
		if ( sel && sel.value ) {
			sel.value = '';
			openModal( sel.dataset.feature );
		}
	} );

	if ( overlay ) {
		overlay.addEventListener( 'click', closeModal );
	}

	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', closeModal );
	}

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && modal.style.display !== 'none' ) {
			closeModal();
		}
	} );
})();
