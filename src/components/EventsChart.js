/**
 * Events Table Component
 */

import { useState, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { fetchMatomoData } from '../utils/api';
import DataTable from './DataTable';

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
            const response = await fetchMatomoData('Events.getName', {
                period: 'range',
                date,
                filter_limit: -1,
            });
            setData(response);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const eventData = useMemo(() => {
        if (Array.isArray(data)) return data;
        if (data && typeof data === 'object') return data.subtable || data.result || [];
        return [];
    }, [data]);

    const columns = useMemo(() => [
        {
            key: 'label',
            label: __('Event Name', 'openmost-site-kit'),
            render: (row) => row.label || 'Unknown Event',
            searchValue: (row) => row.label || '',
            sortValue: (row) => (row.label || '').toLowerCase(),
        },
        {
            key: 'nb_events',
            label: __('Events', 'openmost-site-kit'),
            numeric: true,
            render: (row) => new Intl.NumberFormat().format(row.nb_events || 0),
            sortValue: (row) => row.nb_events || 0,
        },
    ], []);

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

    if (!eventData || eventData.length === 0) {
        return (
            <div className="omsk-chart-empty">
                {__('No events data available', 'openmost-site-kit')}
            </div>
        );
    }

    return (
        <DataTable
            columns={columns}
            data={eventData}
            defaultSort={{ key: 'nb_events', dir: 'desc' }}
        />
    );
};

export default EventsChart;
