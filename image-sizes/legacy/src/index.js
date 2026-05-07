import Blogs from "./pages/Blogs";
import { render } from '@wordpress/element';

if( window.location.pathname.includes('index.php') || ! window.location.pathname.includes('php') ) {
	render(<Blogs />, document.getElementById('cx-posts'));
}