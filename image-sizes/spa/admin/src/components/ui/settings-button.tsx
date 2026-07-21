import * as React from 'react';
import { __ } from '@wordpress/i18n';
import SettingsIcon from './settings-icon';

interface SettingsButtonProps {
	onClick: () => void;
	icon?: React.ReactNode;
}

export default function SettingsButton({ onClick, icon }: SettingsButtonProps) {
	return (
		<button
			onClick={onClick}
			className="inline-flex items-center gap-2 justify-center whitespace-nowrap rounded-[8px] text-[14px] font-medium transition-colors border border-[#40189D] text-thumbpress-primary bg-[#40189D0D] cursor-pointer w-[126px] h-[40px]"
		>
			{icon || <SettingsIcon />}
			{__( 'Settings', 'image-sizes' )}
		</button>
	);
}
