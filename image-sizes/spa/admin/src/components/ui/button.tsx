import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '../../lib/utils';

const buttonVariants = cva(
	'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
	{
		variants: {
			variant: {
				solid: 'bg-thumbpress-primary text-white border border-thumbpress-primary',
				outline: 'border border-thumbpress-primary bg-white text-thumbpress-primary',
			},
		},
		defaultVariants: {
			variant: 'solid',
		},
	}
);

export interface ButtonProps
	extends React.ButtonHTMLAttributes<HTMLButtonElement>,
		VariantProps<typeof buttonVariants> {}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
	( { className, variant, ...props }, ref ) => {
		return (
			<button
				className={ cn( buttonVariants( { variant, className } ) ) }
				ref={ ref }
				{ ...props }
			/>
		);
	}
);
Button.displayName = 'Button';

export { Button, buttonVariants };
