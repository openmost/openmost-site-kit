/**
 * Top Pages Table Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { fetchMatomoData } from '../utils/api';

const TopPagesChart = ({ date, period }) => {
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadData();
    }, [date, period]);

    const loadData = async () => {
        setLoading(true);
        setError(null);

        try {
            console.log('[TopPagesChart] Fetching data with params:', { method: 'Actions.getPageUrls', period, date, flat: 1, filter_limit: 10 });
            const response = await fetchMatomoData('Actions.getPageUrls', {
                period: 'range',
                date,
                flat: 1,
                filter_limit: 10,
            });
            console.log('[TopPagesChart] Response received:', response);

            if (!response || !Array.isArray(response) || response.length === 0) {
                console.error('[TopPagesChart] No data or empty array received');
            }

            setData(response);
        } catch (err) {
            console.error('[TopPagesChart] Error loading data:', err);
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="omsk-chart-loading">
                <Spinner />
            </div>
        );
    }

    if (error) {
        return <div className="omsk-chart-error">{error}</div>;
    }

    // Handle both array and object responses from Matomo
    let pageData = [];
    if (Array.isArray(data)) {
        pageData = data;
    } else if (data && typeof data === 'object') {
        // If data is an object, try to extract array from common keys
        pageData = data.subtable || data.result || [];
    }

    if (!pageData || pageData.length === 0) {
        return (
            <div className="omsk-chart-empty">
                {__('No pages data available', 'openmost-site-kit')}
            </div>
        );
    }

    return (
        <div className="omsk-table-container">
            <table className="omsk-table">
                <thead>
                    <tr>
                        <th>{__('Page URL', 'openmost-site-kit')}</th>
                        <th className="omsk-table-number">{__('Visits', 'openmost-site-kit')}</th>
                        <th className="omsk-table-number">{__('Page Views', 'openmost-site-kit')}</th>
                    </tr>
                </thead>
                <tbody>
                    {pageData.slice(0, 10).map((item, index) => (
                        <tr key={index}>
                            <td className="omsk-table-label">
                                <span className="omsk-table-rank">{index + 1}</span>
                                <span className="omsk-table-text">{item.label || item.url || 'Unknown'}</span>
                            </td>
                            <td className="omsk-table-number">
                                {new Intl.NumberFormat().format(item.nb_visits || 0)}
                            </td>
                            <td className="omsk-table-number">
                                {new Intl.NumberFormat().format(item.nb_hits || 0)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default TopPagesChart;
