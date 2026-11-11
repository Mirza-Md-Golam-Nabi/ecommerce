<?php

namespace App\Filament\Support;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Runs a domain Action from a Filament action and turns business-rule errors
 * (ValidationException with Bangla messages) into a danger notification.
 */
final class DomainAction
{
    /**
     * @param  Closure(): mixed  $callback
     * @return bool Whether the action succeeded.
     */
    public static function run(Closure $callback, string $successTitle): bool
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title((string) collect($exception->errors())->flatten()->first())
                ->send();

            return false;
        }

        Notification::make()->success()->title($successTitle)->send();

        return true;
    }
}
