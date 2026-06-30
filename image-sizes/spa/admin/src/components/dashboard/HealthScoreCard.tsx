import React from 'react';
import { type DashboardStats } from '../../api';
import { CheckCircle2, AlertCircle } from 'lucide-react';

type Props = {};

const HealthScoreCard = ( { score, stats }: { score: number; stats: DashboardStats } ) => {
	const radius = 70;
	const circumference = 2 * Math.PI * radius;
	const strokeDashoffset = circumference - (score / 100) * circumference;

	const color = score >= 80 ? '#0FDA7A' : score >= 50 ? '#FFCE28' : '#FD3738';
	const bgTrack = 'rgba(255,255,255,0.4)';

	const statusLabel = score >= 80 ? 'Good' : score >= 50 ? 'Normal' : 'Danger';
	const statusColor =
		score >= 80 ? '#0FDA7A' : score >= 50 ? '#FFCE28' : '#FD3738';
	const statusBg =
		score >= 80 ? '#0FDA7A26' : score >= 50 ? '#FFCE2826' : '#FD373826';

	const quickFacts = stats.quick_facts ?? [];

	return (
		<div className="bg-[#1E0F53] rounded-xl 2xl:px-10 lg:px-6 py-6 flex items-center 2xl:gap-12 lg:gap-4 text-white shadow-[0px_8px_72px_0px_rgba(242,237,255,0.4)]">
			<div className="flex flex-col shrink-0">
				<p className="2xl:text-base lg:text-sm font-medium text-[#E9E9EA] mb-1 text-left">
					Overall
				</p>
				<p className="2xl:text-2xl lg:text-[18px] font-semibold text-white mb-4">Health Score</p>
				<div className="relative 2xl:w-[190px] lg:w-[110px] aspect-square">
					<svg viewBox="0 0 160 160" className="w-full h-full">
						<circle
							cx="80"
							cy="80"
							r={radius}
							fill="none"
							stroke={bgTrack}
							strokeWidth="20"
						/>
						<circle
							cx="80"
							cy="80"
							r={radius}
							fill="none"
							stroke={color}
							strokeWidth="20"
							strokeLinecap="round"
							strokeDasharray={circumference}
							strokeDashoffset={strokeDashoffset}
							transform="rotate(-90 80 80)"
							className="transition-all duration-700"
						/>
					</svg>
					<div className="absolute inset-0 flex items-center justify-center">
						<span className="2xl:text-[38px] lg:text-[24px] font-bold text-white">{score}%</span>
					</div>
				</div>
				<div className="flex items-center justify-center gap-2 my-4">
					<span className="2xl:text-base lg:text-sm font-normal text-white">Status:</span>

					<div
						style={{ backgroundColor: statusBg, borderRadius: '20px' }}
						className="2xl:px-3 lg:px-2 py-1 flex items-center justify-center gap-2"
					>
						<div
							style={{ backgroundColor: statusColor }}
							className="w-2 h-2 rounded-full"
						></div>
						<span
							style={{ color: statusColor, fontSize: '12px' }}
							className="font-medium"
						>
							{statusLabel}
						</span>
					</div>
				</div>
			</div>

			<div className="flex flex-col gap-2">
				<p className="text-base font-medium text-[#D2D2D5] mb-6">Quick Facts</p>
				<div className=" border-l border-[#FFFFFF80] pl-4 flex flex-col gap-5">
					{quickFacts.map((fact, i) => (
						<div key={i} className="flex items-center gap-[10px]">
							{fact.ok ? (
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M5 13L8.52642 15.8211C9.35374 16.483 10.5536 16.3848 11.2624 15.5973L19 7" stroke="#0DDE95" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							) : (
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M11.8459 15.6484C11.2954 15.6484 10.834 16.1099 10.834 16.6604C10.834 17.2108 11.2954 17.6723 11.8459 17.6723C12.3761 17.6723 12.8578 17.2108 12.8335 16.6846C12.8578 16.1058 12.4004 15.6484 11.8459 15.6484Z" fill="#FF0000"/>
									<path d="M21.2113 19.2352C21.8468 18.1383 21.8508 16.8309 21.2194 15.738L14.8807 4.76074C14.2533 3.65572 13.12 3 11.849 3C10.578 3 9.4447 3.65977 8.81731 4.75669L2.47056 15.7461C1.83912 16.8511 1.84317 18.1666 2.4827 19.2635C3.11414 20.3483 4.24344 21 5.50632 21H18.1674C19.4344 21 20.5718 20.3402 21.2113 19.2352ZM19.8351 18.4419C19.4829 19.049 18.8596 19.4093 18.1634 19.4093H5.50227C4.81416 19.4093 4.19487 19.0571 3.85082 18.4621C3.50272 17.859 3.49867 17.1385 3.84677 16.5314L10.1935 5.54599C10.5376 4.94288 11.1528 4.58669 11.849 4.58669C12.5412 4.58669 13.1605 4.94693 13.5045 5.55003L19.8472 16.5354C20.1872 17.1264 20.1832 17.8388 19.8351 18.4419Z" fill="#FF0000"/>
									<path d="M11.5957 8.54419C11.114 8.68181 10.8145 9.11896 10.8145 9.64921C10.8387 9.96897 10.859 10.2928 10.8833 10.6126C10.9521 11.8309 11.0209 13.025 11.0897 14.2433C11.114 14.6562 11.4337 14.9557 11.8466 14.9557C12.2595 14.9557 12.5833 14.6359 12.6035 14.219C12.6035 13.9681 12.6035 13.7374 12.6278 13.4824C12.6723 12.7012 12.7209 11.92 12.7654 11.1388C12.7897 10.6328 12.8342 10.1268 12.8585 9.62087C12.8585 9.43873 12.8342 9.27682 12.7654 9.11491C12.559 8.66158 12.0773 8.43086 11.5957 8.54419Z" fill="#FF0000"/>
								</svg>
							)}
							<span className="2xl:text-base lg:text-xs font-normal text-white">
								{fact.text}
							</span>
						</div>
					))}
				</div>
			</div>
		</div>
	);
};

export default HealthScoreCard;
