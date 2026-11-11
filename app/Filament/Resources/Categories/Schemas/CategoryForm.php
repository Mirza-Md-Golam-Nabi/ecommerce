<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Models\Product;
use App\Rules\UniqueStoreSlug;
use App\Services\Catalog\CategoryTree;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ক্যাটাগরি')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('নাম (বাংলা)')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('slug')
                            ->label('স্লাগ (URL)')
                            ->helperText('ইংরেজি ছোট হাতের অক্ষর, সংখ্যা ও ড্যাশ। যেমন: spices → ওয়েবসাইটে /spices')
                            ->required()
                            ->maxLength(120)
                            ->regex(UniqueStoreSlug::REGEX)
                            ->rule(fn (?Category $record): UniqueStoreSlug => new UniqueStoreSlug($record)),
                        Select::make('parent_id')
                            ->label('মূল ক্যাটাগরি')
                            ->placeholder('কোনোটি নয় — এটি শীর্ষ ক্যাটাগরি')
                            ->helperText('যে ক্যাটাগরিতে পণ্য আছে, তার নিচে সাব-ক্যাটাগরি রাখা যায় না।')
                            ->options(fn (?Category $record): array => self::parentOptions($record))
                            ->in(fn (?Category $record): array => array_keys(self::parentOptions($record)))
                            ->searchable(),
                        TextInput::make('icon')
                            ->label('আইকন (ইমোজি)')
                            ->placeholder('🥦')
                            ->maxLength(20),
                        FileUpload::make('image')
                            ->label('ছবি')
                            ->helperText('ক্যাটাগরি টাইলে দেখাবে।')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('categories')
                            ->maxSize(2048)
                            ->automaticallyResizeImagesMode('contain')
                            ->automaticallyResizeImagesToWidth('600')
                            ->automaticallyResizeImagesToHeight('600')
                            ->columnSpanFull(),
                    ]),

                Section::make('প্রদর্শন')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('সক্রিয়')
                            ->default(true)
                            ->inline(false),
                        Toggle::make('show_on_home')
                            ->label('হোমপেজে দেখাবে')
                            ->helperText('"জনপ্রিয় ক্যাটাগরি" অংশে।')
                            ->live()
                            ->inline(false),
                        TextInput::make('home_sort_order')
                            ->label('হোমপেজে ক্রম')
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->visible(fn (Get $get): bool => (bool) $get('show_on_home')),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function parentOptions(?Category $record): array
    {
        $categoryIdsWithProducts = Product::query()->distinct()->pluck('category_id')->all();

        return app(CategoryTree::class)->parentOptions($record, $categoryIdsWithProducts);
    }
}
