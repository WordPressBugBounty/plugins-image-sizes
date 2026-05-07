const thumbpress_modal = ( show = true ) => {
	const modal = document.getElementById( 'image-sizes-modal' );
	if ( show ) {
		modal.style.display = '';
	} else {
		modal.style.display = 'none';
	}
}