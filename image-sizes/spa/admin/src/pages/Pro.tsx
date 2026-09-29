import React from 'react';
import { __ } from '@wordpress/i18n';
import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';

// components
import Hero from '../components/pro-page/Hero';
import Features from '../components/pro-page/Features';
import Comparison from '../components/pro-page/Comparison';
import Pricing from '../components/pro-page/Pricing';
import Testimonials from '../components/pro-page/Testimonials';
import FAQ from '../components/pro-page/FAQ';
import CTA from '../components/pro-page/CTA';

export default function Pro() {
	return (
		<>
			<Header title={__( 'Pro', 'image-sizes' )} />

			<PluginPage>
				<div className="overflow-x-hidden 2xl:rounded-3xl xl:rounded-2xl bg-white 2xl:w-full mx-auto">
					<Hero />
					<Features />
					<Comparison />
					<Testimonials />
					<Pricing />
					<FAQ />
					<CTA />
				</div>
			</PluginPage>
		</>
	);
}
