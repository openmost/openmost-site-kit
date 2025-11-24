/**
 * Post Analytics Metabox Component
 * Displays analytics for a specific post/page
 * Rewritten for better reliability and cleaner design
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    SelectControl,
    Spinner,
    Notice,
    Button,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import ReactECharts from 'echarts-for-react';

const PostAnalytics = ({ postId }) => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [data, setData] = useState(null);
    const [dateRange, setDateRange] = useState('last28');

    const loadStats = useCallback(async () => {
        if (!postId) return;

        setLoading(true);
        setError(null);

        try {
            const response = await apiFetch({
                path: `/openmost-site-kit/v1/post-stats/${postId}?period=day&date=${dateRange}`,
            });
            setData(response);
        } catch (err) {
            console.error('[PostAnalytics] Error:', err);
            setError(err.message || __('Failed to load analytics', 'openmost-site-kit'));
        } finally {
            setLoading(false);
        }
    }, [postId, dateRange]);

    useEffect(() => {
        loadStats();
    }, [loadStats]);

    const formatNumber = (num) => {
        if (num === undefined || num === null) return '0';
        return new Intl.NumberFormat().format(parseInt(num) || 0);
    };

    const formatDuration = (seconds) => {
        if (!seconds) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    // Parse data for display
    const parseStats = (responseData) => {
        if (!responseData || !responseData.current) {
            return null;
        }

        const current = responseData.current;
        const comparison = responseData.comparison;

        // Handle both array and object responses from Matomo
        let totals = {
            visits: 0,
            uniqueVisitors: 0,
            pageviews: 0,
            bounceRate: 0,
            avgTimeOnPage: 0,
        };

        let chartData = {
            dates: [],
            visits: [],
            pageviews: [],
        };

        let count = 0;

        // Process current data
        if (typeof current === 'object') {
            // If it's an object with date keys
            const entries = Array.isArray(current) ? current : Object.entries(current);

            entries.forEach((entry) => {
                let date, values;

                if (Array.isArray(entry)) {
                    [date, values] = entry;
                } else if (typeof entry === 'object') {
                    date = entry.date || '';
                    values = entry;
                }

                if (values && typeof values === 'object') {
                    // For charts
                    if (date) {
                        chartData.dates.push(date);
                        chartData.visits.push(parseInt(values.nb_visits || 0));
                        chartData.pageviews.push(parseInt(values.nb_pageviews || 0));
                    }

                    // For totals
                    totals.visits += parseInt(values.nb_visits || 0);
                    totals.uniqueVisitors += parseInt(values.nb_uniq_visitors || 0);
                    totals.pageviews += parseInt(values.nb_pageviews || 0);
                    totals.bounceRate += parseFloat(values.bounce_rate || 0);
                    totals.avgTimeOnPage += parseFloat(values.avg_time_on_page || 0);
                    count++;
                }
            });

            // Average out rates
            if (count > 0) {
                totals.bounceRate = totals.bounceRate / count;
                totals.avgTimeOnPage = totals.avgTimeOnPage / count;
            }
        } else if (current.nb_visits !== undefined) {
            // Single aggregated response
            totals.visits = parseInt(current.nb_visits || 0);
            totals.uniqueVisitors = parseInt(current.nb_uniq_visitors || 0);
            totals.pageviews = parseInt(current.nb_pageviews || 0);
            totals.bounceRate = parseFloat(current.bounce_rate || 0);
            totals.avgTimeOnPage = parseFloat(current.avg_time_on_page || 0);
        }

        // Process comparison for trend
        let comparisonTotals = null;
        if (comparison && typeof comparison === 'object') {
            comparisonTotals = {
                visits: 0,
                uniqueVisitors: 0,
                pageviews: 0,
            };

            const compEntries = Array.isArray(comparison) ? comparison : Object.values(comparison);
            compEntries.forEach((values) => {
                if (values && typeof values === 'object') {
                    comparisonTotals.visits += parseInt(values.nb_visits || 0);
                    comparisonTotals.uniqueVisitors += parseInt(values.nb_uniq_visitors || 0);
                    comparisonTotals.pageviews += parseInt(values.nb_pageviews || 0);
                }
            });
        }

        return { totals, chartData, comparisonTotals, postUrl: responseData.post_url };
    };

    const calculateTrend = (current, previous) => {
        if (!previous || previous === 0) return null;
        return ((current - previous) / previous * 100).toFixed(1);
    };

    if (loading) {
        return (
            <div className="omsk-post-analytics-loading">
                <Spinner />
                <span>{__('Loading analytics...', 'openmost-site-kit')}</span>
            </div>
        );
    }

    if (error) {
        return (
            <div className="omsk-post-analytics-error">
                <Notice status="error" isDismissible={false}>
                    {error}
                </Notice>
                <Button variant="secondary" onClick={loadStats} style={{ marginTop: '10px' }}>
                    {__('Retry', 'openmost-site-kit')}
                </Button>
            </div>
        );
    }

    const parsedData = parseStats(data);

    if (!parsedData || (parsedData.totals.visits === 0 && parsedData.totals.pageviews === 0)) {
        return (
            <div className="omsk-post-analytics">
                <div className="omsk-post-analytics-header">
                    <span className="omsk-post-analytics-title">
                        {__('Analytics', 'openmost-site-kit')}
                    </span>
                    <SelectControl
                        value={dateRange}
                        options={[
                            { label: __('Last 7 days', 'openmost-site-kit'), value: 'last7' },
                            { label: __('Last 14 days', 'openmost-site-kit'), value: 'last14' },
                            { label: __('Last 28 days', 'openmost-site-kit'), value: 'last28' },
                            { label: __('Last 90 days', 'openmost-site-kit'), value: 'last90' },
                        ]}
                        onChange={setDateRange}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                </div>
                <Notice status="info" isDismissible={false}>
                    {__('No analytics data available for this page yet. Make sure tracking is enabled and the page has been visited.', 'openmost-site-kit')}
                </Notice>
            </div>
        );
    }

    const { totals, chartData, comparisonTotals } = parsedData;

    // Chart configuration
    const chartOption = {
        tooltip: {
            trigger: 'axis',
            backgroundColor: 'rgba(255, 255, 255, 0.95)',
            borderColor: '#e0e0e0',
            borderWidth: 1,
            textStyle: { color: '#1e1e1e' },
        },
        legend: {
            data: [__('Visits', 'openmost-site-kit'), __('Page Views', 'openmost-site-kit')],
            bottom: 0,
            itemWidth: 12,
            itemHeight: 12,
        },
        grid: {
            top: 20,
            left: 40,
            right: 20,
            bottom: 50,
        },
        xAxis: {
            type: 'category',
            data: chartData.dates,
            axisLine: { lineStyle: { color: '#e0e0e0' } },
            axisLabel: {
                color: '#646970',
                fontSize: 11,
                formatter: (value) => {
                    const parts = value.split('-');
                    return parts.length === 3 ? `${parts[1]}/${parts[2]}` : value;
                },
            },
        },
        yAxis: {
            type: 'value',
            axisLine: { show: false },
            axisTick: { show: false },
            axisLabel: { color: '#646970', fontSize: 11 },
            splitLine: { lineStyle: { color: '#f0f0f1' } },
        },
        series: [
            {
                name: __('Visits', 'openmost-site-kit'),
                type: 'line',
                data: chartData.visits,
                smooth: true,
                symbol: 'circle',
                symbolSize: 6,
                itemStyle: { color: '#3858e9' },
                lineStyle: { width: 2 },
                areaStyle: {
                    color: {
                        type: 'linear',
                        x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(56, 88, 233, 0.2)' },
                            { offset: 1, color: 'rgba(56, 88, 233, 0)' },
                        ],
                    },
                },
            },
            {
                name: __('Page Views', 'openmost-site-kit'),
                type: 'line',
                data: chartData.pageviews,
                smooth: true,
                symbol: 'circle',
                symbolSize: 6,
                itemStyle: { color: '#10a37f' },
                lineStyle: { width: 2 },
                areaStyle: {
                    color: {
                        type: 'linear',
                        x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(16, 163, 127, 0.2)' },
                            { offset: 1, color: 'rgba(16, 163, 127, 0)' },
                        ],
                    },
                },
            },
        ],
    };

    const TrendIndicator = ({ current, previous }) => {
        const trend = calculateTrend(current, previous);
        if (trend === null) return null;

        const isPositive = parseFloat(trend) > 0;
        const isNeutral = parseFloat(trend) === 0;

        return (
            <span className={`omsk-trend ${isNeutral ? 'neutral' : (isPositive ? 'positive' : 'negative')}`}>
                {isNeutral ? '→' : (isPositive ? '↑' : '↓')} {Math.abs(parseFloat(trend))}%
            </span>
        );
    };

    return (
        <div className="omsk-post-analytics">
            <div className="omsk-post-analytics-header">
                <span className="omsk-post-analytics-title">
                    {__('Analytics', 'openmost-site-kit')}
                </span>
                <SelectControl
                    value={dateRange}
                    options={[
                        { label: __('Last 7 days', 'openmost-site-kit'), value: 'last7' },
                        { label: __('Last 14 days', 'openmost-site-kit'), value: 'last14' },
                        { label: __('Last 28 days', 'openmost-site-kit'), value: 'last28' },
                        { label: __('Last 90 days', 'openmost-site-kit'), value: 'last90' },
                    ]}
                    onChange={setDateRange}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                />
            </div>

            <div className="omsk-post-analytics-metrics">
                <div className="omsk-metric">
                    <span className="omsk-metric-value">{formatNumber(totals.visits)}</span>
                    <span className="omsk-metric-label">{__('Visits', 'openmost-site-kit')}</span>
                    <TrendIndicator current={totals.visits} previous={comparisonTotals?.visits} />
                </div>
                <div className="omsk-metric">
                    <span className="omsk-metric-value">{formatNumber(totals.pageviews)}</span>
                    <span className="omsk-metric-label">{__('Page Views', 'openmost-site-kit')}</span>
                    <TrendIndicator current={totals.pageviews} previous={comparisonTotals?.pageviews} />
                </div>
                <div className="omsk-metric">
                    <span className="omsk-metric-value">{totals.bounceRate.toFixed(0)}%</span>
                    <span className="omsk-metric-label">{__('Bounce Rate', 'openmost-site-kit')}</span>
                </div>
                <div className="omsk-metric">
                    <span className="omsk-metric-value">{formatDuration(totals.avgTimeOnPage)}</span>
                    <span className="omsk-metric-label">{__('Avg. Time', 'openmost-site-kit')}</span>
                </div>
            </div>

            {chartData.dates.length > 0 && (
                <div className="omsk-post-analytics-chart">
                    <ReactECharts
                        option={chartOption}
                        style={{ height: '200px' }}
                        opts={{ renderer: 'svg' }}
                    />
                </div>
            )}
        </div>
    );
};

export default PostAnalytics;
