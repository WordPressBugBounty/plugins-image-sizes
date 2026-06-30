import React from 'react';
import { applyFilters } from '@wordpress/hooks';
import { Toaster } from 'sonner';
import { createRoot } from 'react-dom/client';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import Layout from './components/layout';
import Dashboard from './pages/Dashboard';
import RegenerateThumbnails from './pages/RegenerateThumbnails';
import DetectUnusedImages from './pages/DetectUnusedImages';
import DetectDuplicateImages from './pages/DetectDuplicateImages';
import DetectLargeImages from './pages/DetectLargeImages';
import CompressImages from './pages/CompressImages';
import ConvertToWebP from './pages/ConvertToWebP';
import ConvertToAvif from './pages/ConvertToAvif';

import TrashFiles from './pages/TrashFiles';
import Settings from './pages/Settings';
import Pro from './pages/Pro';

interface RouteData {
	path: string;
	component: string;
}

interface NavItemData {
	to: string;
	label: string;
	icon: string;
	pro?: boolean;
}

interface ThumbpressNavGlobal {
	items?: NavItemData[];
	routes?: RouteData[];
}


const componentMap: Record<string, React.ComponentType> = {
	Dashboard,
	RegenerateThumbnails,
	DetectUnusedImages,
	DetectDuplicateImages,
	DetectLargeImages,
	CompressImages,
	ConvertToWebP,
	ConvertToAvif,
	TrashFiles,
	Settings,
	Pro,
};

const defaultRoutes: RouteData[] = [
	{ path: '/', component: 'Dashboard' },
	{ path: '/thumbnails', component: 'RegenerateThumbnails' },
	{ path: '/unused-images', component: 'DetectUnusedImages' },
	{ path: '/duplicate-images', component: 'DetectDuplicateImages' },
	{ path: '/large-images', component: 'DetectLargeImages' },
	{ path: '/compress-images', component: 'CompressImages' },
	{ path: '/convert-to-webp', component: 'ConvertToWebP' },
	{ path: '/convert-to-avif', component: 'ConvertToAvif' },
	{ path: '/trashed-files', component: 'TrashFiles' },
	{ path: '/settings', component: 'Settings' },
	{ path: '/pro', component: 'Pro' },
];

const defaultNavItems: NavItemData[] = [
	{ to: '/', label: 'Dashboard', icon: 'DashboardIcon' },
	{ to: '/thumbnails', label: 'Thumbnails', icon: 'ThumbnailsIcon' },
	{ to: '/unused-images', label: 'Unused Images', icon: 'UnusedImagesIcon', pro: true },
	{ to: '/duplicate-images', label: 'Duplicate Images', icon: 'DuplicateImageIcon', pro: true },
	{ to: '/large-images', label: 'Large Images', icon: 'LargeImageIcon', pro: true },
	{ to: '/compress-images', label: 'Compress Images', icon: 'CompressImageIcon', pro: true },
	{ to: '/convert-to-webp', label: 'Convert to WebP', icon: 'ConvertToWebPIcon' },
	{ to: '/convert-to-avif', label: 'Convert to AVIF', icon: 'ConvertToAvifIcon', pro: true },
	{ to: '/trashed-files', label: "Trashed Files", icon: 'TrashIcon', pro: true },
	{ to: '/settings', label: 'Settings', icon: 'SettingsIcon' },
	{ to: '/pro', label: 'Pro', icon: 'ProIcon' },
];

function App() {
	const localizedRoutes = window.thumbpress_nav?.routes || [];
	const localizedNavItems = window.thumbpress_nav?.items || [];

	const routes = localizedRoutes.length > 0 ? localizedRoutes : defaultRoutes;
	const navItems = localizedNavItems.length > 0 ? localizedNavItems : defaultNavItems;

	// Allow pro plugins to register their components via WordPress hooks.
	const filteredComponentMap: any = applyFilters( 'thumbpress_component_map', componentMap );

	// Remove duplicates based on 'to' path
	const uniqueNavItems = navItems.filter((item, index, self) =>
		index === self.findIndex((i) => i.to === item.to)
	);

	return (
		<>
			<div id="thumbpress-portal" />
			<HashRouter>
			<Toaster position="top-right" richColors />
			<Routes>
				<Route element={ <Layout navItems={ uniqueNavItems } /> }>
					{ routes.map( ( route ) => {
						const Component = filteredComponentMap[ route.component ];
						if ( ! Component ) return null;
						return (
							<Route
								key={ route.path }
								path={ route.path }
								element={ <Component /> }
							/>
						);
					} ) }
					<Route path="*" element={ <Navigate to="/" replace /> } />
				</Route>
			</Routes>
		</HashRouter>
		</>
	);
}

const container = document.getElementById( 'image-sizes_render' );

if ( container ) {
	const root = createRoot( container );
	root.render( <App /> );
}
