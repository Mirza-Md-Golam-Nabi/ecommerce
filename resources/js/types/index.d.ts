export interface StoreInfo {
    name: string;
    phone: string | null;
    /** Default delivery charge in poisha. */
    delivery_charge: number;
}

/** Visible category node for the sidebar (from CategoryTree::navigation()). */
export interface NavCategory {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    icon: string | null;
    is_leaf: boolean;
}

/** Light bag summary shared with every page. PHP sends `[]` for an empty items map. */
export interface BagSummary {
    count: number;
    subtotal: number;
    items: Record<string, number> | [];
}

/** App\Http\Resources\ProductCardResource. Money in poisha. */
export interface ProductCard {
    id: number;
    name: string;
    slug: string;
    unit_label: string;
    price: number;
    compare_at_price: number | null;
    discount_percent: number | null;
    image: string | null;
    placeholder_icon: string | null;
    is_available: boolean;
    is_weighted: boolean;
    max_quantity: number | null;
}

/** App\Http\Resources\ProductDetailResource. */
export interface ProductDetail extends ProductCard {
    description: string | null;
    images: string[];
}

export interface Breadcrumb {
    name: string;
    slug: string;
}

export interface CategoryTileData {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    image: string | null;
}

/** Paginated list delivered through Inertia::scroll(). */
export interface ScrollData<T> {
    data: T[];
}

export interface BagLine {
    product: ProductCard;
    quantity: number;
    line_total: number;
    can_be_ordered: boolean;
}

export interface BagDetails {
    items: BagLine[];
    count: number;
    subtotal: number;
    delivery_charge: number;
    total: number;
    has_weighted: boolean;
}

/** Props shared with every page by App\Http\Middleware\HandleInertiaRequests. */
export interface SharedProps {
    store: StoreInfo;
    navigation: NavCategory[];
    bag: BagSummary;
    [key: string]: unknown;
}

declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: SharedProps;
    }
}
