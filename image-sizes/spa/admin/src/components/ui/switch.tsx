import { Switch as SwitchPrimitive } from '@base-ui/react/switch';

import { cn } from '../../lib/utils';

function Switch({
	className,
	...props
}: SwitchPrimitive.Root.Props) {
	return (
		<SwitchPrimitive.Root
			data-slot="switch"
			className={cn(
				'peer group/switch relative inline-flex shrink-0 items-center rounded-full transition-all outline-none cursor-pointer h-[24px] w-[48px] border data-[checked]:border-thumbpress-primary data-[unchecked]:border-[#8b8b8b] data-[disabled]:cursor-not-allowed data-[disabled]:opacity-30',
				className,
			)}
			{...props}
		>
			<span className="absolute left-1.5 rtl:left-auto rtl:right-1.5 text-[10px] font-semibold text-thumbpress-primary opacity-0 group-data-[checked]/switch:opacity-100 transition-opacity select-none">
				ON
			</span>
			<span className="absolute right-1 rtl:right-auto rtl:left-1 text-[10px] font-semibold text-[#8b8b8b] opacity-0 group-data-[unchecked]/switch:opacity-100 transition-opacity select-none">
				OFF
			</span>
			<SwitchPrimitive.Thumb
				data-slot="switch-thumb"
				className="pointer-events-none block size-[18px] rounded-full data-[checked]:bg-thumbpress-primary data-[unchecked]:bg-[#8b8b8b] shadow-sm ring-0 transition-transform data-[checked]:translate-x-[25px] data-[unchecked]:translate-x-[3px] rtl:data-[checked]:-translate-x-[25px] rtl:data-[unchecked]:-translate-x-[3px]"
			/>
		</SwitchPrimitive.Root>
	);
}

export { Switch };
