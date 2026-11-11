<?php

use App\Settings\StoreSettings;
use App\Support\Theme;

it('uses the default theme when none is selected', function () {
    expect(Theme::current()->key)->toBe('emerald_gold');
});

it('uses the theme selected in settings', function () {
    app(StoreSettings::class)->save(['theme' => 'plum_champagne']);

    expect(Theme::current()->key)->toBe('plum_champagne');
});

it('falls back to the default for an unknown theme', function () {
    app(StoreSettings::class)->save(['theme' => 'deleted_theme']);

    expect(Theme::current()->key)->toBe('emerald_gold');
});

it('defines the same color tokens in every preset', function () {
    $presets = config('themes.presets');
    $expected = array_keys($presets[config('themes.default')]['colors']);

    foreach ($presets as $preset) {
        expect(array_keys($preset['colors']))->toBe($expected);
    }
});

it('renders css variables and skips non-hex values', function () {
    $theme = new Theme('test', 'Test', [
        'primary' => '#0F3D2E',
        'primary_fg' => '#FFFFFF',
        'accent' => 'red;}body{display:none',
    ]);

    expect($theme->cssVariables())->toBe('--c-primary:#0F3D2E;--c-primary-fg:#FFFFFF;');
});
