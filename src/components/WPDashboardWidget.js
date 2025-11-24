/**
 * WordPress Dashboard Widget Component
 * Compact KPI widgets with trends for the WP Dashboard
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner, Button } from '@wordpress/components';
import { external } from '@wordpress/icons';
import { fetchMatomoData } from '../utils/api';

/**
 * Format number with locale
 */
const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    return parseInt(num).toLocaleString();
};

/**
 * Format time in seconds to readable format
 */
const formatTime = (seconds) => {
    if (!seconds || isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

/**
 * Format percentage
 */
const formatPercent = (value) => {
    if (value === null || value === undefined) return '0%';
    if (typeof value === 'string' && value.includes('%')) return value;
    return `${parseFloat(value).toFixed(1)}%`;
};

/**
 * Calculate trend (comparison between current and previous)
 */
const calculateTrend = (current, previous) => {
    if (!previous || previous === 0) return null;
    const change = ((current - previous) / previous) * 100;
    return {
        value: Math.abs(change).toFixed(0),
        direction: change >= 0 ? 'up' : 'down',
        isPositive: change >= 0,
    };
};

/**
 * KPI Card Component
 */
const KPICard = ({ title, value, trend, color = '#2271b1' }) => {
    return (
        <div className="omsk-kpi-card">
            <div className="omsk-kpi-content">
                <div className="omsk-kpi-value" style={{ color }}>{value}</div>
                <div className="omsk-kpi-title">{title}</div>
            </div>
            {trend && (
                <div className={`omsk-kpi-trend ${trend.isPositive ? 'positive' : 'negative'}`}>
                    <span className="trend-icon">{trend.direction === 'up' ? '↑' : '↓'}</span>
                    <span className="trend-value">{trend.value}%</span>
                </div>
            )}
        </div>
    );
};

/**
 * Main WP Dashboard Widget Component
 */
const WPDashboardWidget = () => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [currentData, setCurrentData] = useState(null);
    const [previousData, setPreviousData] = useState(null);

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        setError(null);

        try {
            // Fetch current and previous period data
            // Use VisitsSummary.get for reliable visitor counts and Actions.get for pageviews
            const [currentVisits, previousVisits, currentActions, previousActions] = await Promise.all([
                fetchMatomoData('VisitsSummary.get', {
                    period: 'range',
                    date: 'last7',
                }),
                fetchMatomoData('VisitsSummary.get', {
                    period: 'range',
                    date: 'previous7',
                }),
                fetchMatomoData('Actions.get', {
                    period: 'range',
                    date: 'last7',
                }),
                fetchMatomoData('Actions.get', {
                    period: 'range',
                    date: 'previous7',
                }),
            ]);

            // Merge data from both APIs
            setCurrentData({
                ...currentVisits,
                nb_pageviews: currentActions?.nb_pageviews || 0,
            });
            setPreviousData({
                ...previousVisits,
                nb_pageviews: previousActions?.nb_pageviews || 0,
            });
        } catch (err) {
            console.error('[WPDashboardWidget] Error loading data:', err);
            setError(err.message || __('Failed to load analytics data', 'openmost-site-kit'));
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="omsk-wp-widget-loading">
                <Spinner />
                <span>{__('Loading analytics...', 'openmost-site-kit')}</span>
            </div>
        );
    }

    if (error) {
        return (
            <div className="omsk-wp-widget-error">
                <p>{__('Unable to load analytics data.', 'openmost-site-kit')}</p>
                <Button variant="secondary" onClick={loadData} size="small">
                    {__('Retry', 'openmost-site-kit')}
                </Button>
            </div>
        );
    }

    // Calculate trends
    const visitsTrend = calculateTrend(currentData?.nb_visits, previousData?.nb_visits);
    const visitorsTrend = calculateTrend(currentData?.nb_uniq_visitors, previousData?.nb_uniq_visitors);
    const pageviewsTrend = calculateTrend(currentData?.nb_pageviews, previousData?.nb_pageviews);

    return (
        <div className="omsk-wp-widget">
            <div className="omsk-wp-widget-period">
                {__('Last 7 days', 'openmost-site-kit')}
            </div>

            {/* KPI Grid */}
            <div className="omsk-kpi-grid">
                <KPICard
                    title={__('Visits', 'openmost-site-kit')}
                    value={formatNumber(currentData?.nb_visits)}
                    color="#2271b1"
                    trend={visitsTrend}
                />
                <KPICard
                    title={__('Visitors', 'openmost-site-kit')}
                    value={formatNumber(currentData?.nb_uniq_visitors)}
                    color="#00a32a"
                    trend={visitorsTrend}
                />
                <KPICard
                    title={__('Pageviews', 'openmost-site-kit')}
                    value={formatNumber(currentData?.nb_pageviews)}
                    color="#d63638"
                    trend={pageviewsTrend}
                />
            </div>

            {/* Additional Stats Row */}
            <div className="omsk-stats-row">
                <div className="omsk-stat-item">
                    <span className="omsk-stat-label">{__('Bounce Rate', 'openmost-site-kit')}</span>
                    <span className="omsk-stat-value">{formatPercent(currentData?.bounce_rate)}</span>
                </div>
                <div className="omsk-stat-divider"></div>
                <div className="omsk-stat-item">
                    <span className="omsk-stat-label">{__('Avg. Time', 'openmost-site-kit')}</span>
                    <span className="omsk-stat-value">{formatTime(currentData?.avg_time_on_site)}</span>
                </div>
            </div>

            {/* Footer link */}
            <div className="omsk-wp-widget-footer">
                <Button
                    href="admin.php?page=openmost-site-kit"
                    variant="link"
                    icon={external}
                    iconPosition="right"
                >
                    {__('View Full Dashboard', 'openmost-site-kit')}
                </Button>
            </div>
        </div>
    );
};

export default WPDashboardWidget;
