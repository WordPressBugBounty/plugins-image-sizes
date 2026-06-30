'use client';

import { Tabs as TabsPrimitive } from '@base-ui/react/tabs';
import { cva, type VariantProps } from 'class-variance-authority';

import { cn } from '../../lib/utils';

function Tabs({
	className,
	orientation = 'horizontal',
	...props
}: TabsPrimitive.Root.Props) {
	return (
		<TabsPrimitive.Root
			data-slot="tabs"
			data-orientation={orientation}
			className={cn(
				'flex gap-2',
				orientation === 'horizontal' ? 'flex-col' : 'flex-row',
				className,
			)}
			{...props}
		/>
	);
}

const tabsListVariants = cva(
	'inline-flex items-center justify-start rounded-lg p-[3px]',
	{
		variants: {
			variant: {
				default: 'bg-gray-100',
				line: 'gap-1 bg-transparent rounded-none p-0',
			},
		},
		defaultVariants: {
			variant: 'default',
		},
	},
);

function TabsList({
	className,
	variant = 'default',
	...props
}: TabsPrimitive.List.Props & VariantProps<typeof tabsListVariants>) {
	return (
		<TabsPrimitive.List
			data-slot="tabs-list"
			data-variant={variant}
			className={cn(tabsListVariants({ variant }), className)}
			{...props}
		/>
	);
}

function TabsTrigger({ className, ...props }: TabsPrimitive.Tab.Props) {
	return (
		<TabsPrimitive.Tab
			data-slot="tabs-trigger"
			className={cn(
				'inline-flex items-center justify-center gap-1.5 2xl:px-3 lg:px-2 py-2 2xl:text-sm lg:text-xs font-medium text-gray-500 transition-all hover:text-gray-900 outline-none disabled:pointer-events-none disabled:opacity-50 border-b-2 rounded-none border-transparent data-[active]:bg-white data-[active]:text-thumbpress-primary data-[active]:border-b-thumbpress-primary',
				'data-[variant=line]/tabs-list:bg-transparent data-[variant=line]/tabs-list:data-active:bg-transparent',
				className,
			)}
			{...props}
		/>
	);
}

function TabsContent({ className, keepMounted = true, ...props }: TabsPrimitive.Panel.Props) {
	return (
		<TabsPrimitive.Panel
			data-slot="tabs-content"
			keepMounted={keepMounted}
			className={cn('flex-1 text-sm outline-none', className)}
			{...props}
		/>
	);
}

export { Tabs, TabsList, TabsTrigger, TabsContent, tabsListVariants };
