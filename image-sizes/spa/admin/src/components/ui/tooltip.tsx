import * as React from 'react';
import { Tooltip as ReactTooltip } from 'react-tooltip';

interface TooltipProps {
	children: React.ReactNode;
	content: string;
}

export function Tooltip({ children, content }: TooltipProps) {
	return (
		<>
			<span data-tooltip-id="thumbpress-tooltip" data-tooltip-content={content}>
				{children}
			</span>
			<ReactTooltip
				id="thumbpress-tooltip"
				style={{
					backgroundColor: '#40189D',
					color: '#fff',
					fontSize: '12px',
					borderRadius: '6px',
					maxWidth: '200px',
					textAlign: 'center',
				}}
			/>
		</>
	);
}
