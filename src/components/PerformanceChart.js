/**
 * Performance Chart Component
 * Shows page load performance metrics as stacked bar chart
 * Each stack layer represents different time spent (network, DOM processing, etc.)
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import ReactECharts from 'echarts-for-react';
import { fetchMatomoData } from '../utils/api';

const PerformanceChart = ({ date, period }) => {
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
            console.log('[PerformanceChart] Fetching data with params:', { method: 'PagePerformance.get', period, date });
            const response = await fetchMatomoData('PagePerformance.get', {
                period,
                date,
            });
            console.log('[PerformanceChart] Response received:', response);

            if (!response || (Array.isArray(response) && response.length === 0) || (typeof response === 'object' && Object.keys(response).length === 0)) {
                console.error('[PerformanceChart] No data or empty data received');
            }

            setData(response);
        } catch (err) {
            console.error('[PerformanceChart] Error loading data:', err);
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
                {__('No performance data available. Enable PagePerformance tracking in your Matomo instance.', 'openmost-site-kit')}
            </div>
        );
    }

    // Prepare data for stacked bar chart showing page speed metrics
    const preparePerformanceData = () => {
        const isObject = !Array.isArray(data);
        const entries = isObject ? Object.entries(data) : data.map((item, idx) => [idx, item]);

        const dates = [];
        const networkTime = [];
        const serverTime = [];
        const transferTime = [];
        const domProcessing = [];
        const domCompletion = [];
        const onLoad = [];

        entries.forEach(([date, values]) => {
            dates.push(date);

            // Use real PagePerformance metrics from Matomo
            // Times are already in seconds, no conversion needed
            const network = parseFloat(values.avg_time_network || 0);
            const server = parseFloat(values.avg_time_server || 0);
            const transfer = parseFloat(values.avg_time_transfer || 0);
            const dom = parseFloat(values.avg_time_dom_processing || 0);
            const completion = parseFloat(values.avg_time_dom_completion || 0);
            const load = parseFloat(values.avg_time_on_load || 0);

            networkTime.push(network.toFixed(2));
            serverTime.push(server.toFixed(2));
            transferTime.push(transfer.toFixed(2));
            domProcessing.push(dom.toFixed(2));
            domCompletion.push(completion.toFixed(2));
            onLoad.push(load.toFixed(2));
        });

        return { dates, networkTime, serverTime, transferTime, domProcessing, domCompletion, onLoad };
    };

    const performanceData = preparePerformanceData();

    // Check if all data is zero
    const hasData = performanceData.networkTime.some(val => parseFloat(val) > 0) ||
                    performanceData.serverTime.some(val => parseFloat(val) > 0) ||
                    performanceData.transferTime.some(val => parseFloat(val) > 0) ||
                    performanceData.domProcessing.some(val => parseFloat(val) > 0) ||
                    performanceData.domCompletion.some(val => parseFloat(val) > 0) ||
                    performanceData.onLoad.some(val => parseFloat(val) > 0);

    if (!hasData) {
        return (
            <div className="omsk-chart-empty">
                {__('No performance data available. Enable PagePerformance tracking in your Matomo instance.', 'openmost-site-kit')}
            </div>
        );
    }

    const option = {
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'shadow',
            },
            formatter: (params) => {
                let tooltip = `<strong>${params[0].axisValue}</strong><br/>`;
                let total = 0;
                params.forEach((item) => {
                    total += parseFloat(item.value || 0);
                    tooltip += `${item.marker} ${item.seriesName}: ${item.value}s<br/>`;
                });
                tooltip += `<strong>Total: ${total.toFixed(2)}s</strong>`;
                return tooltip;
            },
        },
        legend: {
            data: [
                __('Network Time', 'openmost-site-kit'),
                __('Server Response', 'openmost-site-kit'),
                __('Transfer Time', 'openmost-site-kit'),
                __('DOM Processing', 'openmost-site-kit'),
                __('DOM Completion', 'openmost-site-kit'),
                __('OnLoad Events', 'openmost-site-kit'),
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
            data: performanceData.dates,
        },
        yAxis: {
            type: 'value',
            name: __('Time (seconds)', 'openmost-site-kit'),
        },
        series: [
            {
                name: __('Network Time', 'openmost-site-kit'),
                type: 'bar',
                stack: 'total',
                data: performanceData.networkTime,
                itemStyle: { color: '#3858e9' },
            },
            {
                name: __('Server Response', 'openmost-site-kit'),
                type: 'bar',
                stack: 'total',
                data: performanceData.serverTime,
                itemStyle: { color: '#10a37f' },
            },
            {
                name: __('Transfer Time', 'openmost-site-kit'),
                type: 'bar',
                stack: 'total',
                data: performanceData.transferTime,
                itemStyle: { color: '#f59e0b' },
            },
            {
                name: __('DOM Processing', 'openmost-site-kit'),
                type: 'bar',
                stack: 'total',
                data: performanceData.domProcessing,
                itemStyle: { color: '#8b5cf6' },
            },
            {
                name: __('DOM Completion', 'openmost-site-kit'),
                type: 'bar',
                stack: 'total',
                data: performanceData.domCompletion,
                itemStyle: { color: '#ec4899' },
            },
            {
                name: __('OnLoad Events', 'openmost-site-kit'),
                type: 'bar',
                stack: 'total',
                data: performanceData.onLoad,
                itemStyle: { color: '#ef4444' },
            },
        ],
    };

    return (
        <>
            <ReactECharts option={option} style={{ height: '350px' }} className="omsk-performance-chart" />
        </>
    );
};

export default PerformanceChart;
