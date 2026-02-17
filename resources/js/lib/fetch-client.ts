/**
 * CSRF-aware fetch wrapper for React Query
 *
 * Handles:
 * - CSRF token extraction from encrypted XSRF-TOKEN cookie
 * - Automatic header injection for Laravel AJAX requests
 * - Proper error handling and 204 No Content responses
 * - SSR-safe document access
 */

/**
 * Extract CSRF token from document.cookie
 *
 * Laravel stores the XSRF-TOKEN as an encrypted, URL-encoded cookie.
 * The VerifyCsrfToken middleware decrypts the X-XSRF-TOKEN header server-side.
 *
 * @returns The CSRF token string, or null if not found or in SSR context
 */
export function getCsrfToken(): string | null {
    // Guard against SSR environments where document is not available
    if (typeof document === 'undefined') {
        return null;
    }

    const cookies = document.cookie.split(';');

    for (const cookie of cookies) {
        const [name, value] = cookie.trim().split('=');

        if (name === 'XSRF-TOKEN') {
            // Laravel encrypts and URL-encodes the XSRF-TOKEN cookie
            return decodeURIComponent(value);
        }
    }

    return null;
}

/**
 * CSRF-aware fetch wrapper for React Query
 *
 * Automatically:
 * - Adds CSRF token header for mutating requests (POST, PUT, PATCH, DELETE)
 * - Sets credentials to 'same-origin' for session-based auth
 * - Adds X-Requested-With: XMLHttpRequest for Laravel AJAX detection
 * - Sets Accept: application/json for all requests
 * - Sets Content-Type: application/json when body is provided (unless FormData)
 * - Throws on non-ok responses with parsed error message
 * - Returns null for 204 No Content responses
 *
 * @template T The expected response type
 * @param url The request URL
 * @param options Optional fetch RequestInit options (headers will be merged)
 * @returns Promise resolving to parsed response or null for 204
 * @throws Error with status and message on non-ok responses
 */
export async function fetchClient<T>(
    url: string,
    options?: RequestInit,
): Promise<T> {
    const csrfToken = getCsrfToken();
    const method = (options?.method || 'GET').toUpperCase();

    // Determine if this is a mutating request
    const isMutating = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

    // Build headers
    const headers = new Headers(options?.headers || {});

    // Always set these headers
    headers.set('Accept', 'application/json');
    headers.set('X-Requested-With', 'XMLHttpRequest');

    // Add CSRF token only for mutating requests
    if (isMutating && csrfToken) {
        headers.set('X-XSRF-TOKEN', csrfToken);
    }

    // Set Content-Type: application/json only if body exists and is not FormData
    if (options?.body && !(options.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
    }

    // Build fetch config
    const config: RequestInit = {
        ...options,
        method,
        headers,
        credentials: 'same-origin',
    };

    // Execute fetch
    const response = await fetch(url, config);

    // Handle non-ok responses
    if (!response.ok) {
        let message = `HTTP ${response.status}`;

        try {
            const error = await response.json();
            message = error.message || message;
        } catch {
            // If response is not JSON, use status message
        }

        throw new Error(message);
    }

    // Handle 204 No Content
    if (response.status === 204) {
        return null as T;
    }

    // Parse and return JSON response
    return response.json();
}
