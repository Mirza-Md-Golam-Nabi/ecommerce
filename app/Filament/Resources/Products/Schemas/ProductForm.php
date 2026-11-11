<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\SaleType;
use App\Filament\Forms\MoneyInput;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Models\Product;
use App\Rules\UniqueStoreSlug;
use App\Services\Catalog\CategoryTree;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('মূল তথ্য')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('নাম (বাংলা)')
                            ->placeholder('রাঁধুনি হলুদ গুঁড়া')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('slug')
                            ->label('স্লাগ (URL)')
                            ->placeholder('radhuni-holud-gura-200g')
                            ->helperText('ইংরেজি ছোট হাতের অক্ষর, সংখ্যা ও ড্যাশ। প্যাক সাইজও লিখুন।')
                            ->required()
                            ->maxLength(200)
                            ->regex(CategoryForm::SLUG_PATTERN)
                            ->rule(fn (?Product $record): UniqueStoreSlug => new UniqueStoreSlug($record)),
                        Select::make('category_id')
                            ->label('ক্যাটাগরি')
                            ->helperText('শুধু শেষ ধাপের ক্যাটাগরি (যার নিচে সাব-ক্যাটাগরি নেই) বাছা যায়।')
                            ->options(fn (): array => app(CategoryTree::class)->leafOptions())
                            ->in(fn (): array => array_keys(app(CategoryTree::class)->leafOptions()))
                            ->searchable()
                            ->required(),
                        TextInput::make('search_keywords')
                            ->label('সার্চ কীওয়ার্ড')
                            ->placeholder('turmeric holud radhuni')
                            ->helperText('ইংরেজি বা বাংলিশ নাম — কাস্টমার এগুলো লিখে খুঁজলেও পাবে।')
                            ->maxLength(500),
                        Textarea::make('description')
                            ->label('বিবরণ')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('দাম ও একক')
                    ->description('সব দাম টাকায় লিখুন।')
                    ->columns(3)
                    ->schema([
                        ToggleButtons::make('sale_type')
                            ->label('বিক্রির ধরন')
                            ->options(SaleType::class)
                            ->default(SaleType::Fixed)
                            ->inline()
                            ->live()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('unit_label')
                            ->label('একক (যেমন দেখাবে)')
                            ->placeholder(fn (Get $get): string => self::isWeighted($get) ? '১ কেজি (± ৫০ গ্রাম)' : '২০০ গ্রাম')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('nominal_weight_grams')
                            ->label('প্রতি এককের ওজন')
                            ->helperText('যেমন ১ কেজি = 1000। আসল ওজন অনুযায়ী দাম এটা দিয়ে হিসাব হবে।')
                            ->suffix('গ্রাম')
                            ->integer()
                            ->minValue(1)
                            ->required(fn (Get $get): bool => self::isWeighted($get))
                            ->visible(fn (Get $get): bool => self::isWeighted($get)),
                        MoneyInput::make('price')
                            ->label(fn (Get $get): string => self::isWeighted($get) ? 'বিক্রয়মূল্য (প্রতি একক)' : 'বিক্রয়মূল্য')
                            ->required(),
                        MoneyInput::make('compare_at_price')
                            ->label('আগের দাম (কাটা দাম)')
                            ->helperText('ছাড় দেখাতে চাইলে। বিক্রয়মূল্যের চেয়ে বেশি হতে হবে।')
                            ->gt('price'),
                        MoneyInput::make('estimated_cost')
                            ->label('আনুমানিক ক্রয়মূল্য')
                            ->helperText('সাধারণত বাজারে কত দামে কেনেন। আসল দাম অর্ডারের সময় বসবে।')
                            ->default(0)
                            ->required(),
                        TextInput::make('max_per_order')
                            ->label('এক অর্ডারে সর্বোচ্চ')
                            ->helperText('খালি রাখলে স্টোর সেটিংসের সীমা প্রযোজ্য।')
                            ->integer()
                            ->minValue(1),
                    ]),

                Section::make('ছবি')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image')
                            ->label('মূল ছবি')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('products')
                            ->maxSize(4096)
                            ->automaticallyResizeImagesMode('contain')
                            ->automaticallyResizeImagesToWidth('1000')
                            ->automaticallyResizeImagesToHeight('1000'),
                        Repeater::make('images')
                            ->label('আরও ছবি')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->grid(2)
                            ->addActionLabel('ছবি যোগ করুন')
                            ->schema([
                                FileUpload::make('path')
                                    ->hiddenLabel()
                                    ->image()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('products')
                                    ->maxSize(4096)
                                    ->automaticallyResizeImagesMode('contain')
                                    ->automaticallyResizeImagesToWidth('1000')
                                    ->automaticallyResizeImagesToHeight('1000')
                                    ->required(),
                            ]),
                    ]),

                Section::make('প্রদর্শন')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('সক্রিয়')
                            ->helperText('বন্ধ করলে ওয়েবসাইটে দেখাবে না।')
                            ->default(true),
                        Toggle::make('is_available')
                            ->label('বাজারে পাওয়া যাচ্ছে')
                            ->helperText('বন্ধ করলে দেখাবে কিন্তু অর্ডার করা যাবে না।')
                            ->default(true),
                    ]),
            ]);
    }

    private static function isWeighted(Get $get): bool
    {
        $saleType = $get('sale_type');

        return $saleType === SaleType::Weighted || $saleType === SaleType::Weighted->value;
    }
}
