"use client"

import { Checkbox as CheckboxPrimitive } from "@base-ui/react/checkbox"

import { cn } from "../../lib/utils"

function Checkbox({ className, ...props }: CheckboxPrimitive.Root.Props) {
	return (
		<CheckboxPrimitive.Root
			data-slot="checkbox"
			className={cn(
				"peer relative flex size-5 shrink-0 items-center justify-center rounded-[4px] border border-[#707070] transition-colors outline-none focus-visible:ring-2 focus-visible:ring-thumbpress-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[checked]:bg-[#40189D] data-[checked]:border-[#40189D] data-[checked]:text-white",
				className
			)}
			{...props}
		>
			<CheckboxPrimitive.Indicator
				data-slot="checkbox-indicator"
				className="grid place-content-center text-current transition-none"
			>
				<svg width="11" height="8" viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M9.57575 0.247832L3.94478 5.95739L1.42425 3.40168C1.09835 3.07124 0.570317 3.07124 0.244421 3.40168C-0.0814738 3.73212 -0.0814738 4.26753 0.244421 4.59797L3.35487 7.75182C3.51713 7.91634 3.73164 8 3.94478 8C4.15792 8 4.37105 7.91774 4.53469 7.75182L10.7556 1.44412C11.0815 1.11368 11.0815 0.578275 10.7556 0.247832C10.4297 -0.0826108 9.90165 -0.0826108 9.57575 0.247832Z" fill="white"/>
				</svg>
			</CheckboxPrimitive.Indicator>
		</CheckboxPrimitive.Root>
	)
}

export { Checkbox }