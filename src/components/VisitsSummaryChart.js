/**
 * Visits Summary Chart Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import ReactECharts from 'echarts-for-react';
import { fetchMatomoData } from '../utils/api';

const VisitsSummaryChart = ({ date, period }) => {
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
            const response = await fetchMatomoData('VisitsSummary.getVisits', {
                period,
                date,
            });
            setData(response);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: '40px' }}>
                <Spinner />
            </div>
        );
    }

    if (error) {
        return <div style={{ color: 'red' }}>{error}</div>;
    }

    if (!data) {
        return <div>{__('No data available', 'openmost-site-kit')}</div>;
    }

    const chartData = Array.isArray(data) ? data : Object.entries(data).map(([key, value]) => ({ date: key, value }));
    const dates = chartData.map(item => item.date || Object.keys(data));
    const values = chartData.map(item => item.value || item);

    const option = {
        tooltip: {
            trigger: 'axis',
        },
        grid: {
            top: 32,
            left: 16,
            right: 32,
            bottom: 16,
            containLabel: true,
        },
        xAxis: {
            type: 'category',
            data: dates.flat(),
        },
        yAxis: {
            type: 'value',
        },
        series: [
            {
                name: __('Visits', 'openmost-site-kit'),
                data: values.flat(),
                type: 'line',
                smooth: true,
                areaStyle: {
                    opacity: 0.3,
                },
            },
        ],
    };

    return <ReactECharts option={option} style={{ height: '400px' }} />;
};

export default VisitsSummaryChart;
