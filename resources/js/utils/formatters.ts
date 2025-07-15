// resources/js/utils/formatters.ts

/**
 * Formats a number as Indonesian Rupiah with thousand/million separators.
 * @param value The number to format.
 * @returns Formatted string, e.g., "Rp1.234.567"
 */
export const formatCurrency = (value: number): string => {
    const valueNumber = parseFloat(String(value));
    return `Rp${valueNumber.toLocaleString('id-ID')}`;
};
