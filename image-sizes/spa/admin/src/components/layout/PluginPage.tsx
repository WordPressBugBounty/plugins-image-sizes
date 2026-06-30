import React from 'react';
import { cn } from '../../lib/utils';

interface PluginPageProps extends React.HTMLAttributes<HTMLDivElement> {}

const PluginPage = React.forwardRef<HTMLDivElement, PluginPageProps>(
	({ className, children }, ref) => {
		return (
			<div ref={ref} className={cn('p-6 pb-0 h-[calc(100%_-_80px)]', className)}>
				{children}
			</div>
		);
	},
);
PluginPage.displayName = 'PluginPage';

export default PluginPage;
