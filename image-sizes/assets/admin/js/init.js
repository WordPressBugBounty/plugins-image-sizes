(function() {
	var links = document.querySelectorAll( '#adminmenu a[href*="page=thumbpress"]' );

	links.forEach( function( link ) {
		var href = link.getAttribute( 'href' );
		if ( ! href ) return;

		var hashIndex = href.indexOf( '#' );
		var hash = hashIndex !== -1 ? href.substring( hashIndex ) : '#/';
		if ( hash === '#' ) hash = '#/';

		link.addEventListener( 'click', function( e ) {
			e.preventDefault();
			if ( window.location.search.indexOf( 'page=thumbpress' ) !== -1 ) {
				window.location.hash = hash;
			} else {
				window.location.href = href;
			}
		});
	});
})();

Object.keys(THUMBPRESS.menus).forEach(key => {

    document.querySelectorAll(`.toplevel_page_${key} .wp-submenu.wp-submenu-wrap > li`).forEach(function(item) {

    	// add .current on-click
        item.addEventListener('click', function() {
            document.querySelectorAll(`.toplevel_page_${key} .wp-submenu.wp-submenu-wrap > li`).forEach(function(li) {
                li.classList.remove('current');
            });
            item.classList.add('current');
        });

        // add .current to current menu
        const link = item.querySelector('a');
        if (link && link.hash && link.hash === window.location.hash) {
            document.querySelectorAll(`.toplevel_page_${key} .wp-submenu.wp-submenu-wrap > li`).forEach(function(li) {
                li.classList.remove('current');
            });
            item.classList.add('current');
        }
    });

    // add # to the first submenu item
    const li = document.querySelector(`#adminmenu li.toplevel_page_${key}`);
    if (li) {
        const firstItem = li.querySelector('a.wp-first-item');
        if (firstItem) {
            const href = firstItem.getAttribute('href');
            if (href) {
                firstItem.setAttribute('href', `${href}#`);
            }
        }
    }
    
});