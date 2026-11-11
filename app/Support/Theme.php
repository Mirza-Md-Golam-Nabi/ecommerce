<?php

namespace App\Support;

use App\Settings\StoreSettings;
use InvalidArgumentException;

/**
 * A storefront color theme preset from config/themes.php.
 */
final class Theme
{
    /**
     * @param  array<string, string>  $colors
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $colors,
    ) {}

    /**
     * The theme selected in store settings, or the default when none/unknown.
     */
    public static function current(): self
    {
        $selected = app(StoreSettings::class)->theme();

        return ($selected !== null ? self::find($selected) : null) ?? self::default();
    }

    public static function default(): self
    {
        return self::find((string) config('themes.default'))
            ?? throw new InvalidArgumentException('The default theme in config/themes.php does not exist.');
    }

    public static function find(string $key): ?self
    {
        $preset = config("themes.presets.{$key}");

        if (! is_array($preset)) {
            return null;
        }

        return new self($key, $preset['label'], $preset['colors']);
    }

    /**
     * @return array<string, string> theme key => label
     */
    public static function options(): array
    {
        return collect(config('themes.presets'))
            ->map(fn (array $preset): string => $preset['label'])
            ->all();
    }

    /**
     * CSS custom property declarations, e.g. `--c-primary:#0F3D2E;--c-primary-fg:#FFFFFF;`.
     * Non-hex values are skipped so config mistakes can never inject CSS.
     */
    public function cssVariables(): string
    {
        return collect($this->colors)
            ->filter(fn (string $value): bool => (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $value))
            ->map(fn (string $value, string $name): string => '--c-'.str_replace('_', '-', $name).':'.$value.';')
            ->implode('');
    }
}
