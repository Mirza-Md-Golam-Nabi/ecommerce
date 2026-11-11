<?php

namespace App\Filament\Pages;

use App\Settings\StoreSettings as Settings;
use App\Support\Money;
use App\Support\Theme;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Admin-editable store settings: store info, storefront theme, order defaults.
 *
 * @property-read Schema $form
 */
class StoreSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'স্টোর সেটিংস';

    protected static ?string $title = 'স্টোর সেটিংস';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(Settings $settings): void
    {
        $this->form->fill([
            'store_name' => $settings->get('store_name'),
            'store_phone' => $settings->storePhone(),
            'theme' => Theme::current()->key,
            'default_delivery_charge' => Money::toTaka($settings->defaultDeliveryCharge()),
            'default_max_qty_per_product' => $settings->defaultMaxQtyPerProduct(),
            'max_pending_orders_per_customer' => $settings->maxPendingOrdersPerCustomer(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('দোকানের তথ্য')
                    ->columns(2)
                    ->schema([
                        TextInput::make('store_name')
                            ->label('দোকানের নাম')
                            ->placeholder(config('app.name'))
                            ->maxLength(100),
                        TextInput::make('store_phone')
                            ->label('হেল্পলাইন নম্বর')
                            ->tel()
                            ->maxLength(20),
                    ]),

                Section::make('ওয়েবসাইটের থিম')
                    ->description('কাস্টমার যে ওয়েবসাইট দেখবে তার রং। সেভ করার পর থেকেই নতুন রং দেখাবে।')
                    ->schema([
                        Select::make('theme')
                            ->label('থিম')
                            ->options(fn (): array => collect(Theme::options())
                                ->mapWithKeys(fn (string $label, string $key): array => [$key => self::themeOptionHtml(Theme::find($key))])
                                ->all())
                            ->allowHtml()
                            ->native(false)
                            ->required()
                            ->in(array_keys(Theme::options())),
                    ]),

                Section::make('অর্ডার')
                    ->columns(3)
                    ->schema([
                        TextInput::make('default_delivery_charge')
                            ->label('ডিফল্ট ডেলিভারি চার্জ')
                            ->helperText('প্রতিটি অর্ডারে আলাদাভাবে বদলানো যাবে।')
                            ->prefix('৳')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        TextInput::make('default_max_qty_per_product')
                            ->label('প্রতি পণ্যের সর্বোচ্চ পরিমাণ')
                            ->helperText('এক অর্ডারে একটি পণ্য কয়টি নেওয়া যাবে। খালি রাখলে কোনো সীমা নেই।')
                            ->integer()
                            ->minValue(1),
                        TextInput::make('max_pending_orders_per_customer')
                            ->label('একজনের সর্বোচ্চ অপেক্ষমান অর্ডার')
                            ->helperText('ভুয়া অর্ডার ঠেকাতে।')
                            ->integer()
                            ->minValue(1)
                            ->required(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('সেভ করুন')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(Settings $settings): void
    {
        $data = $this->form->getState();

        $settings->save([
            'store_name' => filled($data['store_name']) ? $data['store_name'] : null,
            'store_phone' => filled($data['store_phone']) ? $data['store_phone'] : null,
            'theme' => $data['theme'],
            'default_delivery_charge' => Money::toPoisha($data['default_delivery_charge']),
            'default_max_qty_per_product' => filled($data['default_max_qty_per_product']) ? (int) $data['default_max_qty_per_product'] : null,
            'max_pending_orders_per_customer' => (int) $data['max_pending_orders_per_customer'],
        ]);

        Notification::make()
            ->title('সেটিংস সেভ হয়েছে')
            ->success()
            ->send();
    }

    /**
     * Option label with color swatches so the admin can preview each theme.
     */
    private static function themeOptionHtml(Theme $theme): string
    {
        $swatches = collect(['primary', 'accent', 'surface', 'success'])
            ->map(fn (string $name): string => sprintf(
                '<span style="display:inline-block;width:18px;height:18px;border-radius:9999px;border:1px solid rgba(0,0,0,.15);background:%s"></span>',
                e($theme->colors[$name]),
            ))
            ->implode('');

        return sprintf(
            '<span style="display:inline-flex;align-items:center;gap:10px"><span style="display:inline-flex;gap:3px">%s</span>%s</span>',
            $swatches,
            e($theme->label),
        );
    }
}
