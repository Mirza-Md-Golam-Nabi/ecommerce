import type { SVGProps } from 'react';

type IconProps = SVGProps<SVGSVGElement>;

function Svg({ children, ...props }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.8}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            {...props}
        >
            {children}
        </svg>
    );
}

export const BagIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="M6 7h12l1 13H5L6 7Z" />
        <path d="M9 7a3 3 0 0 1 6 0" />
    </Svg>
);

export const SearchIcon = (props: IconProps) => (
    <Svg {...props}>
        <circle cx="11" cy="11" r="7" />
        <path d="m20 20-3.5-3.5" />
    </Svg>
);

export const MenuIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="M4 7h16M4 12h16M4 17h16" />
    </Svg>
);

export const CloseIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="M6 6l12 12M18 6 6 18" />
    </Svg>
);

export const PlusIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="M12 5v14M5 12h14" />
    </Svg>
);

export const MinusIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="M5 12h14" />
    </Svg>
);

export const TrashIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
    </Svg>
);

export const ChevronRightIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="m9 6 6 6-6 6" />
    </Svg>
);

export const ChevronDownIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="m6 9 6 6 6-6" />
    </Svg>
);

export const UserIcon = (props: IconProps) => (
    <Svg {...props}>
        <circle cx="12" cy="8" r="4" />
        <path d="M4 21a8 8 0 0 1 16 0" />
    </Svg>
);

export const CheckIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="m5 12 5 5 9-10" />
    </Svg>
);

export const PhoneIcon = (props: IconProps) => (
    <Svg {...props}>
        <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" />
    </Svg>
);
