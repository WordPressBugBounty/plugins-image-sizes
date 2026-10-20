import React from 'react';
import { __ } from '@wordpress/i18n';

import {
	Accordion,
	AccordionContent,
	AccordionItem,
	AccordionTrigger,
} from '../ui/accordion';

const FAQ = () => {
	const faqs = [
		{
			question: __( 'What image formats does ThumbPress convert?', 'image-sizes' ),
			answer:
				__( 'Free version converts images to WebP (25-35% smaller files) and single images to AVIF. Pro adds bulk AVIF conversion (50% smaller than WebP) plus intelligent compression for all formats. Both reduce file size without visible quality loss, dramatically improving site speed and lowering bandwidth costs.', 'image-sizes' ),
		},
		{
			question: __( 'How much can I save on storage and bandwidth?', 'image-sizes' ),
			answer:
				__( 'Most sites save 30-60% of image storage. WebP reduces files by 25-35%, AVIF by 50% more than WebP. Smaller images also cut bandwidth costs and improve Core Web Vitals scores for better SEO rankings.', 'image-sizes' ),
		},
		{
			question: __( 'What\'s the difference between Free and Pro?', 'image-sizes' ),
			answer:
				__( 'Free: Basic optimization (WebP, regenerate thumbnails). Pro: Advanced detection (find unused images, duplicates, oversized files), bulk compression, bulk AVIF conversion, one-click automation. Best for sites with hundreds or thousands of images where optimization pays for itself.', 'image-sizes' ),
		},
		{
			question: __( 'How does pricing work? Is there a trial?', 'image-sizes' ),
			answer:
				__( 'No hidden fees. Free version is your trial. Upgrade anytime if you need Pro features. 30-day money-back guarantee on all paid plans.', 'image-sizes' ),
		},
		{
			question: __( 'Can I use Pro on multiple sites? How many?', 'image-sizes' ),
			answer:
				__( 'Yes. Professional plan covers up to 5 sites. Agency plan covers unlimited. Perfect for freelancers and agencies managing multiple client sites. Licensing is per-plan, not per-site.', 'image-sizes' ),
		},
		{
			question: __( 'Is my media library safe? Will images be deleted automatically?', 'image-sizes' ),
			answer:
				__( 'Unused images, duplicates, and large files are flagged for review - you decide what to remove. Image conversion is different: it replaces each image with the converted file and deletes the original, including when it runs automatically on upload. Back up your uploads before you convert.', 'image-sizes' ),
		},
		{
			question: __( 'What if it doesn\'t deliver results or doesn\'t work on my site?', 'image-sizes' ),
			answer:
				__( '100% money-back within 30 days, no questions asked. Our team provides setup assistance to ensure it works perfectly. We also stand behind the results - if optimization doesn\'t improve your metrics, we refund immediately.', 'image-sizes' ),
		},
		{
			question: __( 'Is it compatible with WooCommerce, Elementor, and my theme?', 'image-sizes' ),
			answer:
				__( 'Yes. ThumbPress works with WooCommerce, Gutenberg, and Elementor. It operates at the WordPress media layer, not inside specific plugins.', 'image-sizes' ),
		},
		{
			question: __( 'How long does bulk processing take? Will it slow my site?', 'image-sizes' ),
			answer:
				__( 'Heavy operations run in the background, so your dashboard and frontend stay responsive. How long a run takes depends on your hosting, your image sizes and how many images you have.', 'image-sizes' ),
		},
		{
			question: __( 'Do I get support? What if I need help implementing?', 'image-sizes' ),
			answer:
				__( 'All customers get support. Professional and Agency plans include priority support. Our team helps with setup, troubleshooting, and optimization recommendations to maximize your results.', 'image-sizes' ),
		},
	];

	return (
		<div className="2xl:px-[300px] lg:px-[80px] py-16">
			<div className="text-center mb-10">
				<h2 className="2xl:text-[32px] lg:text-[28px] font-semibold text-thumbpress-title mb-2 max-w-[600px] mx-auto">
					{__( 'Frequently Asked Questions', 'image-sizes' )}
				</h2>
			</div>

            <Accordion defaultValue={['item-0']} className="space-y-0">
                {faqs.map((faq, index) => (
                    <AccordionItem key={index} value={`item-${index}`} className="border-b">
                        <AccordionTrigger>{faq.question}</AccordionTrigger>
                        <AccordionContent>
                            <p className='text-[#2C3033] text-base'>{faq.answer}</p>
                        </AccordionContent>
                    </AccordionItem>
                ))}
            </Accordion>
		</div>
	);
};

export default FAQ;
