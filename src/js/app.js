/**
 * Use CURL to fetch Matomo without CORS
 *
 * @param params
 * @returns {Promise<Response<any, Record<string, any>, number>>}
 */
async function fetchMatomoApi(params) {
    let adminAjax = omsk_app_params.admin_ajax;
    let formData = new FormData();
    Object.keys(params).forEach(value => {
        formData.append(value, params[value]);
    })

    return await fetch(`${adminAjax}?action=omsk_handle_fetch_matomo_api`, {
        method: 'POST',
        body: formData,
    });
}
window.fetchMatomoApi = fetchMatomoApi;
