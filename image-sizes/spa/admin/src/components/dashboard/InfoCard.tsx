import React from 'react';

const InfoCard = ( { icon: Icon, customIcon, title, value, sub, onClick }: {
    icon?: React.ElementType;
    customIcon?: React.ReactNode;
    title: string;
    value: string | number;
    sub: string;
    onClick?: () => void;
} ) => {
	return (
		<div
			className={ `bg-white rounded-xl border border-gray-100 2xl:p-6 lg:p-4` }
		>
			<div className="flex items-center justify-between mb-3">
				<div className="flex items-center gap-2">
					{ customIcon ? customIcon : Icon && (
						<div className="2xl:w-10 2xl:h-10 lg:w-8 lg:h-8 rounded-lg bg-[#F0EBFF] flex items-center justify-center">
							<Icon size={ 16 } className="text-thumbpress-primary" />
						</div>
					) }
					<span className="2xl:text-lg lg:text-sm text-thumbpress-title">{ title }</span>
				</div>
				{ onClick && (
                    <button onClick={ onClick } className='w-10 h-10 rounded-xl bg-[#F6F8FA] flex items-center justify-center'>
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.23603 6.40655L9.23603 0.749698M9.23603 0.749698L3.57917 0.749698M9.23603 0.749698L0.750745 9.23498" stroke="#0D0D12" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                ) }
			</div>

			<p className="2xl:text-3xl lg:text-2xl font-medium text-thumbpress-title mb-1">{ value }</p>
			<p className="2xl:text-base lg:text-sm text-[#777980]">{ sub }</p>
		</div>
	);
};

export default InfoCard;
