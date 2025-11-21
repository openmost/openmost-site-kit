/**
 * Dashboard Page Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Card,
    CardBody,
    CardHeader,
    SelectControl,
    Notice,
    Spinner,
    Flex,
    FlexBlock,
    Button,
} from '@wordpress/components';
import { external } from '@wordpress/icons';
import { getSettings } from '../../utils/api';
import ChannelTypesChart from '../../components/ChannelTypesChart';
import TopPagesChart from '../../components/TopPagesChart';
import EventsChart from '../../components/EventsChart';
import VisitsOverviewChart from '../../components/VisitsOverviewChart';
import PerformanceChart from '../../components/PerformanceChart';
import KPICards from '../../components/KPICards';

const Dashboard = () => {
    const [loading, setLoading] = useState(true);
    const [settings, setSettings] = useState(null);
    const [dateRange, setDateRange] = useState('last28');
    const [period, setPeriod] = useState('day');

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        try {
            const data = await getSettings();
            setSettings(data);

            if (!data.host || !data.idSite || !data.tokenAuth) {
                // Settings incomplete
            }
        } catch (error) {
            console.error('Failed to load settings', error);
        } finally {
            setLoading(false);
        }
    };

    const isConfigured = settings?.host && settings?.idSite && settings?.tokenAuth;

    if (loading) {
        return (
            <div style={{ padding: '20px', textAlign: 'center' }}>
                <Spinner />
            </div>
        );
    }

    if (!isConfigured) {
        const canManageOptions = window.openmostSiteKit?.canManageOptions || false;

        return (
            <div className="omsk-dashboard">
                <h1>{__('Dashboard', 'openmost-site-kit')}</h1>

                <p className="description">
                    {__('Take a look at your data.', 'openmost-site-kit')}
                </p>

                <Notice status="warning" isDismissible={false}>
                    <p>
                        {__('The dashboard requires Matomo configuration to display analytics data.', 'openmost-site-kit')}
                    </p>
                    <p>
                        {canManageOptions
                            ? __('Please configure your Matomo host, site ID, and auth token in the Settings page.', 'openmost-site-kit')
                            : __('Please contact your site administrator to configure Matomo settings.', 'openmost-site-kit')
                        }
                    </p>
                </Notice>
            </div>
        );
    }

    return (
        <div className="omsk-dashboard">
            <div className="omsk-dashboard-header">
                <div className="omsk-dashboard-title-section">
                    <h1>{__('Dashboard', 'openmost-site-kit')}</h1>

                </div>
                <div className="omsk-dashboard-controls">
                    <SelectControl
                        label={__('Date Range', 'openmost-site-kit')}
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
            </div>

            {/* KPI Row - Visits, Page Views, Bounce Rate */}
            <KPICards date={dateRange} period={period} />

            {/* Main Charts Row - Visits Overview (75%) and Channel Types (25%) */}
            <div className="omsk-dashboard-row omsk-main-charts">
                <Card className="omsk-chart-visits-overview">
                    <CardHeader>
                        <h2>{__('Visits Overview', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <VisitsOverviewChart date={dateRange} period={period} />
                    </CardBody>
                </Card>

                <Card className="omsk-chart-channels">
                    <CardHeader>
                        <h2>{__('Channel Types', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <ChannelTypesChart date={dateRange} period={period} />
                    </CardBody>
                </Card>
            </div>

            {/* Content Analysis Row - Top Pages (50%) and Events (50%) */}
            <div className="omsk-dashboard-row omsk-content-charts">
                <Card className="omsk-chart-pages">
                    <CardHeader>
                        <h2>{__('Top Pages', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <TopPagesChart date={dateRange} period={period} />
                    </CardBody>
                </Card>

                <Card className="omsk-chart-events">
                    <CardHeader>
                        <h2>{__('Events', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <EventsChart date={dateRange} period={period} />
                    </CardBody>
                </Card>
            </div>

            {/* Performance Metrics Row - Full Width Stacked Bar */}
            <div className="omsk-dashboard-row omsk-performance-row">
                <Card className="omsk-chart-performance">
                    <CardHeader>
                        <h2>{__('Page Performance', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <PerformanceChart date={dateRange} period={period} />
                    </CardBody>
                </Card>
            </div>
        </div>
    );
};

export default Dashboard;
