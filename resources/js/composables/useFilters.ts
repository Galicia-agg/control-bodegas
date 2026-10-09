import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';

/**
 * Keeps a reactive set of list filters in sync with the URL query string.
 */
export function useFilters<T extends Record<string, unknown>>(
    url: string,
    initial: T,
    debounce = 300,
) {
    const filters = reactive({ ...initial }) as T;

    const apply = () => {
        const query = Object.fromEntries(
            Object.entries(filters).filter(
                ([, value]) =>
                    value !== '' &&
                    value !== null &&
                    value !== undefined &&
                    value !== false,
            ),
        );

        router.get(url, query as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    watchDebounced(() => ({ ...filters }), apply, { debounce, deep: true });

    const reset = () => {
        for (const key of Object.keys(filters)) {
            (filters as Record<string, unknown>)[key] =
                typeof initial[key] === 'boolean' ? false : '';
        }
    };

    return { filters, reset };
}
