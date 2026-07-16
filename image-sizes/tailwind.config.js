module.exports = {
	content: [
		'./app/**/*.php',
		'./views/**/*.html',
		'./spa/public/src/**/*.{jsx,tsx}',
		'./spa/admin/src/**/*.{jsx,tsx,ts}',
	],
	theme: {
		extend: {
			colors: {
				thumbpress: {
					primary: '#40189D',
					title: '#1B2538',
					body: '#3C3C42',
					border: '#40189D1A',
					'pro-yellow': '#FFCF5C',
					red: '#FF3A52',
				},
			},
			fontFamily: {
				inter: [
					'Inter',
					'system-ui',
					'-apple-system',
					'BlinkMacSystemFont',
					'"Segoe UI"',
					'Roboto',
					'"Helvetica Neue"',
					'Arial',
					'sans-serif',
				],
			},
			keyframes: {
				'accordion-down': {
					from: { height: 0 },
					to: { height: 'var(--accordion-panel-height)' },
				},
				'accordion-up': {
					from: { height: 'var(--accordion-panel-height)' },
					to: { height: 0 },
				},
			},
			animation: {
				'accordion-down': 'accordion-down 0.2s ease-out',
				'accordion-up': 'accordion-up 0.2s ease-out',
			},
		},
	},
	plugins: [],
};
