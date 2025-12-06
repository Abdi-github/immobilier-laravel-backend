import { format, parseISO } from 'date-fns';
import type { Property, Translatable } from '@/Types';

export function formatCurrency(amount: number, currency = 'CHF'): string {
    return new Intl.NumberFormat('de-CH', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

export function formatDate(date: string | Date, fmt = 'dd.MM.yyyy'): string {
    const d = typeof date === 'string' ? parseISO(date) : date;
    return format(d, fmt);
}

export function formatDateTime(date: string | Date): string {
    return formatDate(date, 'dd.MM.yyyy HH:mm');
}

export function formatAddress(property: Pick<Property, 'address' | 'postal_code'>): string {
    return [property.address, property.postal_code].filter(Boolean).join(', ');
}

export function getTranslation(
    obj: Translatable | null | undefined,
    locale: string,
    fallback = '',
): string {
    if (!obj) return fallback;
    return (
        obj[locale as keyof Translatable] ??
        obj.en ??
        Object.values(obj).find((v) => v) ??
        fallback
    );
}
