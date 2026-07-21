import React from 'react';
import { __ } from '@wordpress/i18n';

type ReviewProps = {
	name: string;
	rating: number;
	comment: string;
	avatar: string;
	link?: string;
};

const ReviewCard = (review: ReviewProps) => {
    const imgUrl = window.THUMBPRESS?.assets_url + '/admin/img/pro/reviews';

	return (
        <div className="2xl:p-6 lg:p-5 rounded-xl flex flex-col justify-between border border-thumbpress-border bg-white 2xl:h-[340px] 2xl:min-w-[410px] lg:h-auto lg:min-w-[32%]">
            <div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 21C8.53043 21 9.03914 20.7893 9.41421 20.4142C9.78929 20.0391 10 19.5304 10 19V13C10 12.4696 9.78929 11.9609 9.41421 11.5858C9.03914 11.2107 8.53043 11 8 11C7.73478 11 7.48043 10.8946 7.29289 10.7071C7.10536 10.5196 7 10.2652 7 10V9C7 8.46957 7.21071 7.96086 7.58579 7.58579C7.96086 7.21071 8.46957 7 9 7C9.26522 7 9.51957 6.89464 9.70711 6.70711C9.89464 6.51957 10 6.26522 10 6V4C10 3.73478 9.89464 3.48043 9.70711 3.29289C9.51957 3.10536 9.26522 3 9 3C7.4087 3 5.88258 3.63214 4.75736 4.75736C3.63214 5.88258 3 7.4087 3 9V19C3 19.5304 3.21071 20.0391 3.58579 20.4142C3.96086 20.7893 4.46957 21 5 21H8Z" stroke="#40189D" stroke-opacity="0.2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M19 21C19.5304 21 20.0391 20.7893 20.4142 20.4142C20.7893 20.0391 21 19.5304 21 19V13C21 12.4696 20.7893 11.9609 20.4142 11.5858C20.0391 11.2107 19.5304 11 19 11C18.7348 11 18.4804 10.8946 18.2929 10.7071C18.1054 10.5196 18 10.2652 18 10V9C18 8.46957 18.2107 7.96086 18.5858 7.58579C18.9609 7.21071 19.4696 7 20 7C20.2652 7 20.5196 6.89464 20.7071 6.70711C20.8946 6.51957 21 6.26522 21 6V4C21 3.73478 20.8946 3.48043 20.7071 3.29289C20.5196 3.10536 20.2652 3 20 3C18.4087 3 16.8826 3.63214 15.7574 4.75736C14.6321 5.88258 14 7.4087 14 9V19C14 19.5304 14.2107 20.0391 14.5858 20.4142C14.9609 20.7893 15.4696 21 16 21H19Z" stroke="#40189D" stroke-opacity="0.2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

                <p className='text-base text-thumbpress-body mt-2.5'>
                    {review.comment}
                </p>
            </div>

            <div className="flex items-center gap-3 2xl:mt-0 lg:mt-8">
                <a href={review.link} target="_blank" rel="noreferrer">
                    <img src={`${imgUrl}/${review.avatar}`} alt={__( 'reviewer', 'image-sizes' )} className='rounded-full 2xl:w-[60px] 2xl:h-[60px] lg:w-[40px] lg:h-[40px] object-cover' />
                </a>

                <div>
                    <a href={review.link} target="_blank" rel="noreferrer">
                    <span className='mb-1 block text-base text-thumbpress-body font-medium'>
                        {review.name}
                    </span>
                    </a>

                    <a href={review.link} target="_blank" rel="noreferrer" className="flex items-center gap-1">
                        {[...Array(review.rating)].map((_, i) => (
                            <svg key={i} width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fillRule="evenodd" clipRule="evenodd" d="M8.38518 13.5708L4.05366 15.848C3.34396 16.2211 2.87838 15.8837 3.01405 15.0927L3.8413 10.2695L0.33702 6.85365C-0.237141 6.29398 -0.060084 5.74692 0.734116 5.63151L5.5769 4.92781L7.74267 0.539503C8.09752 -0.179504 8.67252 -0.180165 9.02769 0.539503L11.1935 4.92781L16.0362 5.63151C16.8297 5.74681 17.008 6.29346 16.4333 6.85365L12.9291 10.2695L13.7563 15.0927C13.8919 15.883 13.4271 16.2215 12.7167 15.848L8.38518 13.5708Z" fill="#F99D1D"/>
                            </svg>
                        ))}
                    </a>
                </div>
            </div>
        </div>
    );
};

export default ReviewCard;
