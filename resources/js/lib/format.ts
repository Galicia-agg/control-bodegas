const LOCALE = 'es-GT';
const CURRENCY = 'GTQ';

const numberFmt = new Intl.NumberFormat(LOCALE, { maximumFractionDigits: 2 });
const currencyFmt = new Intl.NumberFormat(LOCALE, {
    style: 'currency',
    currency: CURRENCY,
});
const compactCurrencyFmt = new Intl.NumberFormat(LOCALE, {
    style: 'currency',
    currency: CURRENCY,
    notation: 'compact',
    maximumFractionDigits: 1,
});

export const formatNumber = (value: string | number | null | undefined) =>
    numberFmt.format(Number(value ?? 0));

export const formatCurrency = (value: string | number | null | undefined) =>
    currencyFmt.format(Number(value ?? 0));

export const formatCompactCurrency = (
    value: string | number | null | undefined,
) => compactCurrencyFmt.format(Number(value ?? 0));

export const formatDate = (value: string | null | undefined) =>
    value
        ? new Intl.DateTimeFormat(LOCALE, { dateStyle: 'medium' }).format(
              new Date(value),
          )
        : '—';

export const formatDateTime = (value: string | null | undefined) =>
    value
        ? new Intl.DateTimeFormat(LOCALE, {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '—';

export const toLocalInput = (date = new Date()) => {
    const offset = date.getTimezoneOffset() * 60000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
};
