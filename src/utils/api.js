/**
 * API utilities for communicating with WordPress REST API
 */

import apiFetch from '@wordpress/api-fetch';

const API_NAMESPACE = 'openmost-site-kit/v1';

/**
 * Get current settings
 */
export const getSettings = async () => {
    return await apiFetch({
        path: `${API_NAMESPACE}/settings`,
        method: 'GET',
    });
};

/**
 * Update settings
 */
export const updateSettings = async (settings) => {
    return await apiFetch({
        path: `${API_NAMESPACE}/settings`,
        method: 'POST',
        data: settings,
    });
};

/**
 * Test Matomo connection
 */
export const testConnection = async (host, idSite, tokenAuth) => {
    return await apiFetch({
        path: `${API_NAMESPACE}/test-connection`,
        method: 'POST',
        data: {
            host,
            idSite,
            tokenAuth,
        },
    });
};

/**
 * Fetch data from Matomo API
 */
export const fetchMatomoData = async (method, params = {}) => {
    return await apiFetch({
        path: `${API_NAMESPACE}/matomo/${method}`,
        method: 'POST',
        data: params,
    });
};

/**
 * Get WordPress user roles
 */
export const getRoles = async () => {
    return await apiFetch({
        path: `${API_NAMESPACE}/roles`,
        method: 'GET',
    });
};
