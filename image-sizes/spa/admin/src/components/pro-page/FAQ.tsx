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
			question: 'What image formats does ThumbPress convert?',
			answer:
				'Free version converts images to WebP (25-35% smaller files). Pro adds AVIF conversion (50% smaller than WebP) plus intelligent compression for all formats. Both reduce file size without visible quality loss, dramatically improving site speed and lowering bandwidth costs.',
		},
		{
			question: 'How much can I save on storage and bandwidth?',
			answer:
				'Most sites save 30-60% of image storage. WebP reduces files by 25-35%, AVIF by 50% more than WebP. A typical site with 5,000 images saves 50-100GB. Smaller images also cut bandwidth costs and improve Core Web Vitals scores for better SEO rankings.',
		},
		{
			question: 'What\'s the difference between Free and Pro?',
			answer:
				'Free: Basic optimization (WebP, regenerate thumbnails). Pro: Advanced detection (find unused images, duplicates, oversized files), bulk compression, AVIF conversion, one-click automation. Best for sites with hundreds or thousands of images where optimization pays for itself.',
		},
		{
			question: 'How does pricing work? Is there a trial?',
			answer:
				'No hidden fees or surprise renewals - renewal costs the same as initial purchase. Free version is your trial. Test WebP conversion and regeneration risk-free. Upgrade anytime if you need Pro features. 30-day money-back guarantee on all paid plans.',
		},
		{
			question: 'Can I use Pro on multiple sites? How many?',
			answer:
				'Yes. Professional plan covers up to 5 sites. Agency plan covers unlimited. Perfect for freelancers and agencies managing multiple client sites. Licensing is per-plan, not per-site.',
		},
		{
			question: 'Is my media library safe? Will images be deleted automatically?',
			answer:
				'Completely safe. ThumbPress shows you exactly what it found and requires your approval before any action. Nothing is ever deleted automatically. Unused images, duplicates, and large files are flagged for review - you decide what to remove.',
		},
		{
			question: 'What if it doesn\'t deliver results or doesn\'t work on my site?',
			answer:
				'100% money-back within 30 days, no questions asked. Our team provides setup assistance to ensure it works perfectly. We also stand behind the results - if optimization doesn\'t improve your metrics, we refund immediately.',
		},
		{
			question: 'Is it compatible with WooCommerce, Elementor, and my theme?',
			answer:
				'Yes. ThumbPress works with WooCommerce, Gutenberg, Elementor, Divi, and all major themes. It operates at the WordPress media layer, not inside specific plugins, so compatibility is universal.',
		},
		{
			question: 'How long does bulk processing take? Will it slow my site?',
			answer:
				'All heavy operations run in the background - zero impact on your site. Most sites process 500-1000 (or more) images per hour depending on hosting. Background processing handles thousands overnight. Your dashboard and frontend stay fast.',
		},
		{
			question: 'Do I get support? What if I need help implementing?',
			answer:
				'All customers get support with 24-hour response time. Professional and Agency plans include priority support. Our team helps with setup, troubleshooting, and optimization recommendations to maximize your results.',
		},
	];

	return (
		<div className="2xl:px-[300px] lg:px-[80px] py-16">
			<div className="text-center mb-10">
				<h2 className="2xl:text-[32px] lg:text-[28px] font-semibold text-thumbpress-title mb-2 max-w-[600px] mx-auto">
					Frequently Asked Questions
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
