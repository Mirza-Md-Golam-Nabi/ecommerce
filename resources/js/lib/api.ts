import { http } from '@inertiajs/react';

/** Error from a JSON endpoint, with the first validation/server message (Bangla) when available. */
export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
    ) {
        super(message);
    }
}

const FALLBACK_MESSAGE = 'কিছু একটা সমস্যা হয়েছে, আবার চেষ্টা করুন।';

/**
 * Small JSON helper on top of Inertia's HTTP client (handles the XSRF token).
 * Used for non-page requests: bag updates, bag details, quick view.
 */
export async function requestJson<T>(method: 'get' | 'put' | 'post' | 'delete', url: string, data?: unknown): Promise<T> {
    try {
        const response = await http.getClient().request({
            method,
            url,
            data,
            headers: { Accept: 'application/json' },
        });

        return JSON.parse(response.data) as T;
    } catch (error) {
        throw toApiError(error);
    }
}

function toApiError(error: unknown): ApiError {
    const response = (error as { response?: { status: number; data: string } }).response;

    if (!response) {
        return new ApiError('ইন্টারনেট সংযোগ পরীক্ষা করুন।', 0);
    }

    try {
        const body = JSON.parse(response.data) as { message?: string; errors?: Record<string, string[]> };
        const firstError = body.errors ? Object.values(body.errors)[0]?.[0] : undefined;

        return new ApiError(firstError ?? body.message ?? FALLBACK_MESSAGE, response.status);
    } catch {
        return new ApiError(FALLBACK_MESSAGE, response.status);
    }
}
