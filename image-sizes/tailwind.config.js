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
				'badge-glow': {
					'0%, 100%': { boxShadow: '0 0 4px 1px rgba(249, 31, 62, 0.45)' },
					'50%': { boxShadow: '0 0 12px 4px rgba(249, 31, 62, 0.8)' },
				},
				// A diagonal highlight sweeps across, then rests until the next cycle.
				'badge-shine': {
					'0%': { transform: 'translateX(-150%) skewX(-20deg)' },
					'40%, 100%': { transform: 'translateX(300%) skewX(-20deg)' },
				},
				// Rests for most of the cycle, then grows and wobbles briefly.
				'badge-wobble': {
					'0%, 60%, 100%': { transform: 'scale(1) rotate(0deg)' },
					'66%': { transform: 'scale(1.12) rotate(-6deg)' },
					'72%': { transform: 'scale(1.12) rotate(6deg)' },
					'78%': { transform: 'scale(1.12) rotate(-4deg)' },
					'84%': { transform: 'scale(1.08) rotate(3deg)' },
					'90%': { transform: 'scale(1.04) rotate(-1deg)' },
				},
			},
			animation: {
				'accordion-down': 'accordion-down 0.2s ease-out',
				'accordion-up': 'accordion-up 0.2s ease-out',
				'badge-glow': 'badge-glow 1.8s ease-in-out infinite',
				'badge-shine': 'badge-shine 3s ease-in-out infinite',
				'badge-attention': 'badge-glow 1.8s ease-in-out infinite, badge-wobble 5s ease-in-out infinite',
			},
		},
	},
	plugins: [],
};
