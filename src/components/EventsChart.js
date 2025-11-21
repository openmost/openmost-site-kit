/**
 * Events Chart Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { fetchMatomoData } from '../utils/api';

const EventsChart = ({ date, period }) => {
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
            console.log('[EventsChart] Fetching data with params:', { method: 'Events.getName', period, date, filter_limit: 10 });
            const response = await fetchMatomoData('Events.getName', {
                period: 'range',
                date,
                filter_limit: 10,
            });
            console.log('[EventsChart] Response received:', response);

            if (!response || !Array.isArray(response) || response.length === 0) {
                console.error('[EventsChart] No data or empty array received');
            }

            setData(response);
        } catch (err) {
            console.error('[EventsChart] Error loading data:', err);
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
    let eventData = [];
    if (Array.isArray(data)) {
        eventData = data;
    } else if (data && typeof data === 'object') {
        // If data is an object, try to extract array from common keys
        eventData = data.subtable || data.result || [];
    }

    if (!eventData || eventData.length === 0) {
        return (
            <div className="omsk-chart-empty">
                {__('No events data available', 'openmost-site-kit')}
            </div>
        );
    }

    return (
        <div className="omsk-table-container">
            <table className="omsk-table">
                <thead>
                    <tr>
                        <th>{__('Event Name', 'openmost-site-kit')}</th>
                        <th className="omsk-table-number">{__('Events', 'openmost-site-kit')}</th>
                        <th className="omsk-table-number">{__('Unique', 'openmost-site-kit')}</th>
                    </tr>
                </thead>
                <tbody>
                    {eventData.slice(0, 10).map((item, index) => (
                        <tr key={index}>
                            <td className="omsk-table-label">
                                <span className="omsk-table-rank">{index + 1}</span>
                                <span className="omsk-table-text">{item.label || 'Unknown Event'}</span>
                            </td>
                            <td className="omsk-table-number">
                                {new Intl.NumberFormat().format(item.nb_events || 0)}
                            </td>
                            <td className="omsk-table-number">
                                {new Intl.NumberFormat().format(item.nb_events_with_value || item.nb_uniq_visitors || 0)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default EventsChart;
