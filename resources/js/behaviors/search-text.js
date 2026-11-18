/**
 * Match Arabic names the way people type them: أحمد = احمد, فاطمة = فاطمه,
 * مصطفى = مصطفي, with diacritics and extra spaces ignored. Arabic digits
 * (٠٥٩٩) count as the same digits as 0599.
 */
export function normalizeSearch(text) {
    return String(text ?? '')
        .toLowerCase()
        .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x0660))
        .replace(/[ً-ْـ]/g, '')
        .replace(/[أإآ]/g, 'ا')
        .replace(/ة/g, 'ه')
        .replace(/ى/g, 'ي')
        .replace(/\s+/g, ' ')
        .trim();
}

/**
 * Whether a searched text matches what was typed. A number of three or more
 * digits also finds a phone however it is spaced (0599 123 456 = 0599123456).
 */
export function matchesSearch(haystack, typed) {
    const term = normalizeSearch(typed);

    if (term === '') {
        return true;
    }

    const text = normalizeSearch(haystack);

    if (text.includes(term)) {
        return true;
    }

    const digits = term.replace(/\D/g, '');

    return digits.length >= 3 && digits === term.replace(/\s/g, '') && text.replace(/\D/g, '').includes(digits);
}
