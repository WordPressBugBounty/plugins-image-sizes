import * as React from 'react';
import { cn } from '../../lib/utils';
import { Tooltip } from './tooltip';

interface CardProps extends React.HTMLAttributes<HTMLDivElement> {
	title?: string;
	tooltip?: string;
	description?: string;
	headerAction?: React.ReactNode;
}

const Card = React.forwardRef<HTMLDivElement, CardProps>(
	( { className, title, tooltip, description, headerAction, children, ...props }, ref ) => (
		<div ref={ ref } className={ cn( 'rounded-lg border bg-white shadow-sm', className ) } { ...props }>
			{ title && (
				<div className="px-5 py-3 border-b border-thumbpress-border flex items-center justify-between gap-6">
					<div className="flex-1 min-w-0">
						<div className="flex items-center gap-2">
							<h3 className="2xl:text-lg lg:text-base font-semibold">{ title }</h3>
							{ tooltip && (
								<Tooltip content={ tooltip }>
									<svg className="cursor-help" width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M8.54973 4.97845C8.54973 4.85198 8.65215 4.75046 8.77772 4.75046H10.2223C10.3478 4.75046 10.4503 4.85198 10.4503 4.97845V6.4221C10.4503 6.54767 10.3478 6.65009 10.2223 6.65009H8.77772C8.65215 6.65009 8.54973 6.54767 8.54973 6.4221V4.97845ZM8.54973 8.77772C8.54973 8.65215 8.65215 8.54973 8.77772 8.54973H10.2223C10.3478 8.54973 10.4503 8.65215 10.4503 8.77772V14.0216C10.4503 14.148 10.3478 14.2495 10.2223 14.2495H8.77772C8.65215 14.2495 8.54973 14.148 8.54973 14.0216V8.77772ZM9.5 0C4.25617 0 0 4.25617 0 9.5C0 14.7438 4.25617 19 9.5 19C14.7438 19 19 14.7438 19 9.5C19 4.25617 14.7438 0 9.5 0ZM9.5 17.0995C5.31063 17.0995 1.90055 13.6894 1.90055 9.5C1.90055 5.31063 5.31063 1.90055 9.5 1.90055C13.6894 1.90055 17.0995 5.31063 17.0995 9.5C17.0995 13.6894 13.6894 17.0995 9.5 17.0995Z" fill="#1B2538"/>
									</svg>
								</Tooltip>
							) }
						</div>
						{ description && <p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1 leading-normal">{ description }</p> }
					</div>
					{ headerAction && <div className="flex-shrink-0">{ headerAction }</div> }
				</div>
			) }
			<div className="p-6">{ children }</div>
		</div>
	)
);
Card.displayName = 'Card';

export default Card;
