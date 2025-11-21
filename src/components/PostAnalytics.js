/**
 * Post Analytics Metabox Component
 * Displays analytics for a specific post/page with multiple metrics
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Card,
    CardBody,
    SelectControl,
    Spinner,
    Notice,
    Flex,
    FlexBlock,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import ReactECharts from 'echarts-for-react';

const PostAnalytics = ({ postId }) => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [stats, setStats] = useState(null);
    const [dateRange, setDateRange] = useState('last7');
    const [period, setPeriod] = useState('day');

    useEffect(() => {
        if (postId) {
            loadStats();
        }
    }, [postId, dateRange, period]);

    const loadStats = async () => {
        setLoading(true);
        setError(null);

        try {
            const data = await apiFetch({
                path: `/openmost-site-kit/v1/post-stats/${postId}?period=${period}&date=${dateRange}`,
            });
            setStats(data);
        } catch (err) {
            setError(err.message || __('Failed to load analytics', 'openmost-site-kit'));
        } finally {
            setLoading(false);
        }
    };

    const calculateTrend = (current, previous) => {
        if (!previous || previous === 0) return null;
        const change = ((current - previous) / previous) * 100;
        return change.toFixed(1);
    };

    const formatNumber = (num) => {
        if (!num) return '0';
        return new Intl.NumberFormat().format(num);
    };

    const formatDuration = (seconds) => {
        if (!seconds) return '0s';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return mins > 0 ? `${mins}m ${secs}s` : `${secs}s`;
    };

    if (loading) {
        return (
            <div style={{ padding: '20px', textAlign: 'center' }}>
                <Spinner />
                <p style={{ marginTop: '10px', color: '#757575' }}>
                    {__('Loading analytics...', 'openmost-site-kit')}
                </p>
            </div>
        );
    }

    if (error) {
        return (
            <Notice status="error" isDismissible={false}>
                {error}
            </Notice>
        );
    }

    if (!stats || !stats.current) {
        return (
            <Notice status="info" isDismissible={false}>
                {__('No analytics data available for this page yet.', 'openmost-site-kit')}
            </Notice>
        );
    }

    // Aggregate data across date range
    const aggregateData = (data) => {
        if (!data) return null;

        const result = {
            nb_visits: 0,
            nb_uniq_visitors: 0,
            nb_pageviews: 0,
            nb_actions: 0,
            bounce_rate: 0,
            avg_time_on_page: 0,
        };

        const dataArray = Array.isArray(data) ? data : Object.values(data);
        let count = 0;

        dataArray.forEach(item => {
            if (item && typeof item === 'object') {
                result.nb_visits += parseInt(item.nb_visits || 0);
                result.nb_uniq_visitors += parseInt(item.nb_uniq_visitors || 0);
                result.nb_pageviews += parseInt(item.nb_pageviews || 0);
                result.nb_actions += parseInt(item.nb_actions || 0);
                result.bounce_rate += parseFloat(item.bounce_rate || 0);
                result.avg_time_on_page += parseFloat(item.avg_time_on_page || 0);
                count++;
            }
        });

        if (count > 0) {
            result.bounce_rate = result.bounce_rate / count;
            result.avg_time_on_page = result.avg_time_on_page / count;
        }

        return result;
    };

    const currentData = aggregateData(stats.current);
    const comparisonData = stats.comparison ? aggregateData(stats.comparison) : null;

    // Prepare chart data
    const prepareChartData = () => {
        const dataArray = Array.isArray(stats.current) ? stats.current : Object.entries(stats.current);
        const dates = [];
        const visits = [];
        const pageviews = [];
        const actions = [];
        const uniqueVisitors = [];

        dataArray.forEach(item => {
            let date, values;
            if (Array.isArray(item)) {
                [date, values] = item;
            } else {
                date = item.date || '';
                values = item;
            }

            dates.push(date);
            visits.push(parseInt(values.nb_visits || 0));
            pageviews.push(parseInt(values.nb_pageviews || 0));
            actions.push(parseInt(values.nb_actions || 0));
            uniqueVisitors.push(parseInt(values.nb_uniq_visitors || 0));
        });

        return { dates, visits, pageviews, actions, uniqueVisitors };
    };

    const chartData = prepareChartData();

    const MetricCard = ({ title, value, previous, icon, color = '#3858e9' }) => {
        const trend = previous ? calculateTrend(value, previous) : null;
        const isPositive = trend > 0;
        const isNeutral = trend === 0;

        return (
            <div style={{
                backgroundColor: 'white',
                border: '1px solid #e0e0e0',
                borderRadius: '8px',
                padding: '20px',
                flex: 1,
                minWidth: '200px',
            }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <div>
                        <div style={{ color: '#757575', fontSize: '14px', marginBottom: '8px' }}>
                            {title}
                        </div>
                        <div style={{ fontSize: '28px', fontWeight: '600', color: '#1e1e1e' }}>
                            {typeof value === 'string' ? value : formatNumber(value)}
                        </div>
                        {trend !== null && (
                            <div style={{
                                marginTop: '8px',
                                fontSize: '12px',
                                color: isNeutral ? '#757575' : (isPositive ? '#10a37f' : '#ef4444'),
                                display: 'flex',
                                alignItems: 'center',
                                gap: '4px',
                            }}>
                                <span>{isNeutral ? '→' : (isPositive ? '↑' : '↓')}</span>
                                <span>{Math.abs(trend)}%</span>
                                <span style={{ color: '#757575' }}>vs previous</span>
                            </div>
                        )}
                    </div>
                    <div style={{
                        width: '40px',
                        height: '40px',
                        borderRadius: '8px',
                        backgroundColor: `${color}15`,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontSize: '20px',
                    }}>
                        {icon}
                    </div>
                </div>
            </div>
        );
    };

    const chartOption = {
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'cross',
            },
        },
        legend: {
            data: [
                __('Visits', 'openmost-site-kit'),
                __('Page Views', 'openmost-site-kit'),
                __('Actions', 'openmost-site-kit'),
                __('Unique Visitors', 'openmost-site-kit'),
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
            data: chartData.dates,
            boundaryGap: false,
        },
        yAxis: {
            type: 'value',
        },
        series: [
            {
                name: __('Visits', 'openmost-site-kit'),
                type: 'line',
                smooth: true,
                data: chartData.visits,
                itemStyle: { color: '#3858e9' },
                areaStyle: { opacity: 0.1 },
            },
            {
                name: __('Page Views', 'openmost-site-kit'),
                type: 'line',
                smooth: true,
                data: chartData.pageviews,
                itemStyle: { color: '#10a37f' },
                areaStyle: { opacity: 0.1 },
            },
            {
                name: __('Actions', 'openmost-site-kit'),
                type: 'line',
                smooth: true,
                data: chartData.actions,
                itemStyle: { color: '#f59e0b' },
                areaStyle: { opacity: 0.1 },
            },
            {
                name: __('Unique Visitors', 'openmost-site-kit'),
                type: 'line',
                smooth: true,
                data: chartData.uniqueVisitors,
                itemStyle: { color: '#8b5cf6' },
                areaStyle: { opacity: 0.1 },
            },
        ],
    };

    return (
        <div className="omsk-post-analytics">
            <Flex justify="space-between" style={{ marginBottom: '20px' }}>
                <FlexBlock>
                    <h3 style={{ margin: 0, fontSize: '16px', fontWeight: '600' }}>
                        {__('Analytics Overview', 'openmost-site-kit')}
                    </h3>
                </FlexBlock>
                <FlexBlock style={{ maxWidth: '200px' }}>
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
                    />
                </FlexBlock>
            </Flex>

            {/* Key Metrics Cards */}
            <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
                gap: '16px',
                marginBottom: '20px',
            }}>
                <MetricCard
                    title={__('Visits', 'openmost-site-kit')}
                    value={currentData.nb_visits}
                    previous={comparisonData?.nb_visits}
                    icon="👁"
                    color="#3858e9"
                />
                <MetricCard
                    title={__('Unique Visitors', 'openmost-site-kit')}
                    value={currentData.nb_uniq_visitors}
                    previous={comparisonData?.nb_uniq_visitors}
                    icon="👤"
                    color="#8b5cf6"
                />
                <MetricCard
                    title={__('Page Views', 'openmost-site-kit')}
                    value={currentData.nb_pageviews}
                    previous={comparisonData?.nb_pageviews}
                    icon="📄"
                    color="#10a37f"
                />
                <MetricCard
                    title={__('Actions', 'openmost-site-kit')}
                    value={currentData.nb_actions}
                    previous={comparisonData?.nb_actions}
                    icon="⚡"
                    color="#f59e0b"
                />
                <MetricCard
                    title={__('Bounce Rate', 'openmost-site-kit')}
                    value={`${currentData.bounce_rate.toFixed(1)}%`}
                    icon="🎯"
                    color="#ef4444"
                />
                <MetricCard
                    title={__('Avg. Time on Page', 'openmost-site-kit')}
                    value={formatDuration(currentData.avg_time_on_page)}
                    icon="⏱"
                    color="#06b6d4"
                />
            </div>

            {/* Trend Chart */}
            <div style={{
                backgroundColor: 'white',
                border: '1px solid #e0e0e0',
                borderRadius: '8px',
                padding: '20px',
            }}>
                <h4 style={{ marginTop: 0, marginBottom: '20px', fontSize: '14px', fontWeight: '600' }}>
                    {__('Trend Analysis', 'openmost-site-kit')}
                </h4>
                <ReactECharts option={chartOption} style={{ height: '300px' }} />
            </div>
        </div>
    );
};

export default PostAnalytics;
