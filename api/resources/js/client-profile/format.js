export function formatMoney(amount, currency = 'UAH', symbols = {}) {
    const value = Number(amount ?? 0);
    const symbol = symbols[currency] ?? currency;
    return `${value.toLocaleString('uk-UA', { maximumFractionDigits: 0 })} ${symbol}`;
}

export function formatDate(iso) {
    if (!iso) return '—';
    const [y, m, d] = iso.split('-');
    return `${d}.${m}.${y}`;
}

export function nestedUrl(template, id) {
    return template.replace('__ID__', String(id));
}

export function initials(name) {
    return (name || '?').trim().charAt(0).toUpperCase();
}

export function primaryPhone(phones = []) {
    return phones.find((p) => p.is_primary) ?? phones[0] ?? null;
}
