<?php

/*
|--------------------------------------------------------------------------
| Storefront color themes
|--------------------------------------------------------------------------
|
| The admin picks one preset in Store Settings. Each color becomes a CSS
| variable `--c-{name}` (underscores become dashes) printed in the root
| view, and Tailwind maps it to utilities like `bg-primary`, `text-accent`.
| To add a theme, add one entry here with the same color keys.
| Colors must be 6-digit hex. `*_fg` is the readable text color on that
| background (keep WCAG AA contrast).
|
*/

return [

    'default' => 'emerald_gold',

    'presets' => [

        'emerald_gold' => [
            'label' => 'Emerald & Gold',
            'colors' => [
                'primary' => '#0F3D2E',
                'primary_fg' => '#FFFFFF',
                'accent' => '#C9A227',
                'accent_fg' => '#1C1C1C',
                'surface' => '#FAF7F0',
                'card' => '#FFFFFF',
                'success' => '#2E7D5B',
                'danger' => '#B42318',
                'ink' => '#1C1C1C',
                'muted' => '#5F6B65',
                'border' => '#E8E2D4',
            ],
        ],

        'midnight_saffron' => [
            'label' => 'Midnight & Saffron',
            'colors' => [
                'primary' => '#14213D',
                'primary_fg' => '#FFFFFF',
                'accent' => '#F59E0B',
                'accent_fg' => '#111827',
                'surface' => '#FFF9F0',
                'card' => '#FFFFFF',
                'success' => '#16A34A',
                'danger' => '#B91C1C',
                'ink' => '#111827',
                'muted' => '#6B7280',
                'border' => '#EDE4D6',
            ],
        ],

        'plum_champagne' => [
            'label' => 'Plum & Champagne',
            'colors' => [
                'primary' => '#3B1E3F',
                'primary_fg' => '#FFFFFF',
                'accent' => '#E6C79C',
                'accent_fg' => '#3B1E3F',
                'surface' => '#FBF8F5',
                'card' => '#FFFFFF',
                'success' => '#3F8F5F',
                'danger' => '#B42318',
                'ink' => '#1F1720',
                'muted' => '#74697A',
                'border' => '#EDE4DD',
            ],
        ],

    ],

];
