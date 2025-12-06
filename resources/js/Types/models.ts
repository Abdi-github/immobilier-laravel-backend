import type {
    AccountStatus,
    AmenityGroup,
    CategorySection,
    LeadPriority,
    LeadSource,
    LeadStatus,
    PropertyStatus,
    TransactionType,
    TranslationStatus,
    UserType,
} from './enums';

// ── Translatable ──

export interface Translatable {
    en?: string;
    fr?: string;
    de?: string;
    it?: string;
}

// ── Auth / User ──

export interface User {
    id: number;
    email: string;
    first_name: string;
    last_name: string;
    full_name: string;
    phone: string | null;
    avatar_url: string | null;
    user_type: UserType;
    agency_id: number | null;
    preferred_language: string;
    status: AccountStatus;
    email_verified_at: string | null;
    last_login_at: string | null;
    two_factor_confirmed_at: string | null;
    created_at: string;
    updated_at: string;
    roles?: Role[];
    permissions?: string[];
    agency?: Agency;
}

// ── Property ──

export interface Property {
    id: number;
    external_id: string;
    external_url: string | null;
    source_language: string | null;
    title: string | null;
    description: string | null;
    category_id: number;
    agency_id: number | null;
    owner_id: number | null;
    transaction_type: TransactionType;
    price: number;
    currency: string;
    additional_costs: number | null;
    rooms: number | null;
    surface: number | null;
    address: string;
    city_id: number;
    canton_id: number;
    postal_code: string | null;
    proximity: string[] | null;
    status: PropertyStatus;
    reviewed_by: number | null;
    reviewed_at: string | null;
    rejection_reason: string | null;
    published_at: string | null;
    created_at: string;
    updated_at: string;
    category?: Category;
    canton?: Canton;
    city?: City;
    owner?: User;
    agency?: Agency;
    reviewer?: User;
    amenities?: Amenity[];
    images?: PropertyImage[];
    primary_image?: PropertyImage | null;
    translations?: PropertyTranslation[];
}

export interface PropertyTranslation {
    id: number;
    property_id: number;
    language: string;
    title: string;
    description: string | null;
    source: string | null;
    quality_score: number | null;
    approval_status: string | null;
}

export interface PropertyImage {
    id: number;
    property_id: number;
    url: string;
    secure_url: string | null;
    thumbnail_url: string | null;
    thumbnail_secure_url: string | null;
    public_id: string | null;
    width: number | null;
    height: number | null;
    format: string | null;
    bytes: number | null;
    alt_text: string | null;
    caption: string | null;
    sort_order: number;
    is_primary: boolean;
    source: string | null;
    original_filename: string | null;
}

// ── Category ──

export interface Category {
    id: number;
    section: CategorySection;
    name: Translatable;
    slug: string;
    icon: string | null;
    sort_order: number;
    is_active: boolean;
}

// ── Amenity ──

export interface Amenity {
    id: number;
    name: Translatable;
    group: AmenityGroup;
    icon: string | null;
    sort_order: number;
    is_active: boolean;
}

// ── Location ──

export interface Canton {
    id: number;
    code: string;
    name: Translatable;
    is_active: boolean;
}

export interface City {
    id: number;
    canton_id: number;
    name: Translatable;
    postal_code: string;
    image_url: string | null;
    is_active: boolean;
    canton?: Canton;
}

// ── Agency ──

export interface Agency {
    id: number;
    name: string;
    slug: string;
    description: Translatable;
    logo_url: string | null;
    website: string | null;
    email: string;
    phone: string | null;
    contact_person: string | null;
    address: string | null;
    city_id: number | null;
    canton_id: number | null;
    postal_code: string | null;
    status: string;
    is_verified: boolean;
    verification_date: string | null;
    total_properties: number;
    canton?: Canton;
    city?: City;
    created_at: string;
    updated_at: string;
}

// ── Lead ──

export interface Lead {
    id: number;
    property_id: number;
    agency_id: number | null;
    user_id: number | null;
    assigned_to: number | null;
    contact_first_name: string;
    contact_last_name: string;
    contact_email: string;
    contact_phone: string | null;
    preferred_contact_method: string;
    preferred_language: string;
    inquiry_type: string;
    message: string | null;
    status: LeadStatus;
    priority: LeadPriority;
    source: LeadSource;
    viewing_scheduled_at: string | null;
    follow_up_date: string | null;
    first_response_at: string | null;
    closed_at: string | null;
    close_reason: string | null;
    created_at: string;
    updated_at: string;
    user?: User;
    property?: Property;
    agency?: Agency;
    assigned_user?: User;
    notes?: LeadNote[];
}

export interface LeadNote {
    id: number;
    lead_id: number;
    created_by: number;
    content: string;
    is_internal: boolean;
    created_at: string;
    creator?: User;
}

// ── Role & Permission ──

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    display_name: Translatable | null;
    description: Translatable | null;
    permissions?: Permission[];
    created_at: string;
    updated_at: string;
}

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
    display_name: Translatable | null;
    description: Translatable | null;
}

// ── Translation ──

export interface Translation {
    id: number;
    translatable_type: string;
    translatable_id: number;
    field: string;
    language: string;
    source_text: string;
    translated_text: string;
    status: TranslationStatus;
    reviewed_by: number | null;
    reviewed_at: string | null;
    rejection_reason: string | null;
    created_at: string;
    updated_at: string;
}

// ── Pagination ──

export interface PaginatedData<T> {
    data: T[];
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
}

// ── Inertia Shared Props ──

export interface SharedProps {
    auth: {
        user: User | null;
        roles: string[];
        permissions: string[];
    } | null;
    flash: {
        success: string | null;
        error: string | null;
        warning: string | null;
        info: string | null;
    };
    locale: string;
    ziggy: {
        url: string;
        port: number | null;
        defaults: Record<string, unknown>;
        routes: Record<string, unknown>;
        location: string;
    };
    [key: string]: unknown;
}
