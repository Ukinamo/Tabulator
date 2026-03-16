/**
 * Get the CSRF token from the XSRF-TOKEN cookie (set by Laravel for same-origin requests).
 * Required for POST/PUT/PATCH/DELETE when using Sanctum's EnsureFrontendRequestsAreStateful.
 */
export function getCsrfToken(): string | null {
    const name = 'XSRF-TOKEN';
    const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
    return match ? decodeURIComponent(match[3]) : null;
}

/**
 * Build headers for API requests. Adds Accept, optional Content-Type, and X-XSRF-TOKEN for state-changing methods.
 * Set contentType: false for FormData/multipart (e.g. file upload) so the browser sets the boundary.
 */
export function apiHeaders(options: {
    contentType?: boolean;
    method?: string;
}): Record<string, string> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
    };
    if (options.contentType !== false) {
        headers['Content-Type'] = 'application/json';
    }
    const method = (options.method ?? 'GET').toUpperCase();
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        const token = getCsrfToken();
        if (token) {
            headers['X-XSRF-TOKEN'] = token;
        }
    }
    return headers;
}
