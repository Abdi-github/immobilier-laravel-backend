export enum UserType {
    END_USER = 'end_user',
    OWNER = 'owner',
    AGENT = 'agent',
    AGENCY_ADMIN = 'agency_admin',
    PLATFORM_ADMIN = 'platform_admin',
    SUPER_ADMIN = 'super_admin',
}

export enum AccountStatus {
    ACTIVE = 'active',
    PENDING = 'pending',
    SUSPENDED = 'suspended',
    DEACTIVATED = 'deactivated',
}

export enum PropertyStatus {
    DRAFT = 'DRAFT',
    PENDING_APPROVAL = 'PENDING_APPROVAL',
    APPROVED = 'APPROVED',
    REJECTED = 'REJECTED',
    PUBLISHED = 'PUBLISHED',
    ARCHIVED = 'ARCHIVED',
}

export enum TransactionType {
    RENT = 'rent',
    BUY = 'buy',
}

export enum CategorySection {
    RESIDENTIAL = 'residential',
    COMMERCIAL = 'commercial',
    LAND = 'land',
    SPECIAL = 'special',
}

export enum AmenityGroup {
    INTERIOR = 'interior',
    EXTERIOR = 'exterior',
    SECURITY = 'security',
    ENERGY = 'energy',
    ACCESSIBILITY = 'accessibility',
}

export enum LeadStatus {
    NEW = 'NEW',
    CONTACTED = 'CONTACTED',
    QUALIFIED = 'QUALIFIED',
    VIEWING_SCHEDULED = 'VIEWING_SCHEDULED',
    NEGOTIATING = 'NEGOTIATING',
    WON = 'WON',
    LOST = 'LOST',
    ARCHIVED = 'ARCHIVED',
}

export enum LeadPriority {
    LOW = 'low',
    MEDIUM = 'medium',
    HIGH = 'high',
    URGENT = 'urgent',
}

export enum LeadSource {
    WEBSITE = 'website',
    PHONE = 'phone',
    EMAIL = 'email',
    REFERRAL = 'referral',
    ADVERTISEMENT = 'advertisement',
    OTHER = 'other',
}

export enum TranslationStatus {
    PENDING = 'pending',
    APPROVED = 'approved',
    REJECTED = 'rejected',
}

export enum SupportedLanguage {
    EN = 'en',
    FR = 'fr',
    DE = 'de',
    IT = 'it',
}
