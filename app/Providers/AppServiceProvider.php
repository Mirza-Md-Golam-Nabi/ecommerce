<?php

namespace App\Providers;

use App\Services\Catalog\CategoryTree;
use App\Settings\StoreSettings;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StoreSettings::class);
        $this->app->singleton(CategoryTree::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->configureFilamentActions();
    }

    /**
     * Project-wide UI rule (docs/07-conventions.md): Edit and Delete are icon-only buttons,
     * with a tooltip so the meaning is still clear on hover.
     */
    private function configureFilamentActions(): void
    {
        EditAction::configureUsing(fn (EditAction $action): EditAction => $action
            ->iconButton()
            ->tooltip('এডিট'));

        DeleteAction::configureUsing(fn (DeleteAction $action): DeleteAction => $action
            ->iconButton()
            ->tooltip('মুছুন'));
    }
}
