(function() {
	var data      = window.thumbpressNoticesData || {};
	var optionUrl = data.optionUrl || '';
	var nonce     = data.nonce || '';

	function dismissViaApi( key ) {
		if ( ! optionUrl ) return;
		fetch( optionUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			body: JSON.stringify({ key: key, value: 1 }),
			credentials: 'same-origin'
		});
	}

	var freshDismiss = document.getElementById( 'thumbpress-fresh-install-dismiss' );
	if ( freshDismiss ) {
		freshDismiss.addEventListener( 'click', function() {
			document.getElementById( 'thumbpress-fresh-install-notice' ).style.display = 'none';
			dismissViaApi( 'thumbpress_fresh_install_notice_dismissed' );
		});
	}

	var proBtn = document.getElementById( 'thumbpress-pro-outdated-notice-dismiss' );
	if ( proBtn ) {
		proBtn.addEventListener( 'click', function() {
			document.getElementById( 'thumbpress-pro-outdated-notice' ).style.display = 'none';
			dismissViaApi( 'thumbpress_pro_outdated_notice_dismissed' );
		});
	}
})();
