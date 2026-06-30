import * as React from 'react';
import ScanAgainIcon from './scan-again-icon';

interface ScanActionButtonProps {
	onClick: () => void;
	text: string;
}

export default function ScanActionButton({ onClick, text }: ScanActionButtonProps) {
	return (
		<button
			onClick={onClick}
			className="inline-flex items-center gap-2 justify-center whitespace-nowrap rounded-[8px] text-[14px] font-medium transition-colors border border-[#D2D2D5] bg-white text-[#4A4C56] cursor-pointer h-[40px] px-4"
		>
			<ScanAgainIcon />
			{text}
		</button>
	);
}
