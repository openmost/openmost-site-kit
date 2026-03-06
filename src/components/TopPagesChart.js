/**
 * Top Pages Table Component
 */

import { useState, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { fetchMatomoData } from '../utils/api';
import DataTable from './DataTable';

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
            const response = await fetchMatomoData('Actions.getPageUrls', {
                period: 'range',
                date,
                flat: 1,
                filter_limit: -1,
            });
            setData(response);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const pageData = useMemo(() => {
        if (Array.isArray(data)) return data;
        if (data && typeof data === 'object') return data.subtable || data.result || [];
        return [];
    }, [data]);

    const columns = useMemo(() => [
        {
            key: 'label',
            label: __('Page URL', 'openmost-site-kit'),
            render: (row) => row.label || row.url || 'Unknown',
            searchValue: (row) => row.label || row.url || '',
            sortValue: (row) => (row.label || row.url || '').toLowerCase(),
        },
        {
            key: 'nb_visits',
            label: __('Visits', 'openmost-site-kit'),
            numeric: true,
            render: (row) => new Intl.NumberFormat().format(row.nb_visits || 0),
            sortValue: (row) => row.nb_visits || 0,
        },
        {
            key: 'nb_hits',
            label: __('Page Views', 'openmost-site-kit'),
            numeric: true,
            render: (row) => new Intl.NumberFormat().format(row.nb_hits || 0),
            sortValue: (row) => row.nb_hits || 0,
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

    if (!pageData || pageData.length === 0) {
        return (
            <div className="omsk-chart-empty">
                {__('No pages data available', 'openmost-site-kit')}
            </div>
        );
    }

    return (
        <DataTable
            columns={columns}
            data={pageData}
            defaultSort={{ key: 'nb_visits', dir: 'desc' }}
        />
    );
};

export default TopPagesChart;
