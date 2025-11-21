/**
 * Visits Overview Chart Component - Line Chart
 * Shows visits and page views over time
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import ReactECharts from 'echarts-for-react';
import { fetchMatomoData } from '../utils/api';

const VisitsOverviewChart = ({ date, period }) => {
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
            console.log('[VisitsOverviewChart] Fetching data with params:', { method: 'API.get', period, date });
            const response = await fetchMatomoData('API.get', {
                period,
                date,
                showColumns: 'nb_visits,nb_pageviews',
            });
            console.log('[VisitsOverviewChart] Response received:', response);

            if (!response || (Array.isArray(response) && response.length === 0) || (typeof response === 'object' && Object.keys(response).length === 0)) {
                console.error('[VisitsOverviewChart] No data or empty data received');
            }

            setData(response);
        } catch (err) {
            console.error('[VisitsOverviewChart] Error loading data:', err);
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

    if (!data || (Array.isArray(data) && data.length === 0) || (typeof data === 'object' && Object.keys(data).length === 0)) {
        return (
            <div className="omsk-chart-empty">
                {__('No data available', 'openmost-site-kit')}
            </div>
        );
    }

    // Prepare data for line chart
    const prepareLineData = () => {
        const isObject = !Array.isArray(data);
        const entries = isObject ? Object.entries(data) : data.map((item, idx) => [idx, item]);

        const dates = [];
        const visits = [];
        const pageviews = [];

        entries.forEach(([date, values]) => {
            dates.push(date);
            visits.push(values.nb_visits || 0);
            pageviews.push(values.nb_pageviews || 0);

            // Debug logging for first entry
            if (dates.length === 1) {
                console.log('[VisitsOverviewChart] Sample data for', date, ':', {
                    nb_visits: values.nb_visits,
                    nb_pageviews: values.nb_pageviews,
                });
            }
        });

        return { dates, visits, pageviews };
    };

    const lineData = prepareLineData();

    // Check if all data is zero
    const hasData = lineData.visits.some(val => val > 0) ||
                    lineData.pageviews.some(val => val > 0);

    if (!hasData) {
        return (
            <div className="omsk-chart-empty">
                {__('No data available', 'openmost-site-kit')}
            </div>
        );
    }

    const option = {
        tooltip: {
            trigger: 'axis',
        },
        legend: {
            data: [
                __('Visits', 'openmost-site-kit'),
                __('Page Views', 'openmost-site-kit'),
            ],
            top: 10,
        },
        grid: {
            top: 60,
            left: 50,
            right: 50,
            bottom: 30,
            containLabel: true,
        },
        xAxis: {
            type: 'category',
            data: lineData.dates,
            boundaryGap: false,
        },
        yAxis: {
            type: 'value',
        },
        series: [
            {
                name: __('Visits', 'openmost-site-kit'),
                type: 'line',
                data: lineData.visits,
                smooth: false,
                itemStyle: { color: '#3858e9' },
                lineStyle: { width: 2 },
            },
            {
                name: __('Page Views', 'openmost-site-kit'),
                type: 'line',
                data: lineData.pageviews,
                smooth: false,
                itemStyle: { color: '#e11d48' },
                lineStyle: { width: 2 },
            },
        ],
    };

    return <ReactECharts option={option} style={{ height: '350px' }} className="omsk-visits-overview-chart" />;
};

export default VisitsOverviewChart;
