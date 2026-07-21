import React from 'react';
import { __ } from '@wordpress/i18n';
import { Star } from 'lucide-react';

import ReviewCard from './components/ReviewCard';

const Testimonials = () => {
	const reviewsData = [
		{
			name: '@pressnine',
			rating: 5,
			comment: __( 'This saved me money when I realised I was about to exceed my host’s image allowance, enabling me to wipe out excess duplicate images.', 'image-sizes' ),
			avatar: '1.png',
			link: 'https://wordpress.org/support/topic/works-great-9314/',
		},
		{
			name: '@tobthijm',
			rating: 5,
			comment:
				__( 'At the moment I’m using it to resize my pictures, I see a lot of things to use in the future to keep the site smaller then it was. So far it’s working fine and the one question I had was answered right away!! Keep up the good work!!!', 'image-sizes' ),
			avatar: '2.png',
			link: 'https://wordpress.org/support/topic/very-nice-plugin-1063/',
		},
		{
			name: '@raygulick',
			rating: 5,
			comment: __( 'This plugin is extremely useful, and a must for sites with a lot of images. I had a problem with activation of the Pro version, and the support team resolved the issue quickly and without fuss.', 'image-sizes' ),
			avatar: '3.png',
			link: 'https://wordpress.org/support/topic/great-features-helpful-support/',
		},
	]

	return (
		<div className="px-[80px] py-16">
			<div className="text-center mb-10">
				<h2 className="2xl:text-[32px] lg:text-[28px] font-semibold text-thumbpress-title mb-2 max-w-[600px] mx-auto leading-[1.4]">
					{__( 'Trusted by 30,000+ WordPress Users Just Like You', 'image-sizes' )}
				</h2>
			</div>
			<div className="flex 2xl:gap-6 lg:gap-4">
				{reviewsData.map((review, index) => (
					<ReviewCard key={index} {...review} />
				))}
			</div>
		</div>
	);
};

export default Testimonials;
