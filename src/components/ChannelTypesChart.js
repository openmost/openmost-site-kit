/**
 * Channel Types Chart Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import ReactECharts from 'echarts-for-react';
import { fetchMatomoData } from '../utils/api';

const ChannelTypesChart = ({ date, period }) => {
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
            console.log('[ChannelTypesChart] Fetching data with params:', { method: 'Referrers.get', period: 'range', date });
            const response = await fetchMatomoData('Referrers.get', {
                period: 'range',
                date,
            });
            console.log('[ChannelTypesChart] Response received:', response);

            if (!response) {
                console.error('[ChannelTypesChart] No data received');
            }

            setData(response);
        } catch (err) {
            console.error('[ChannelTypesChart] Error loading data:', err);
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

    if (!data || (typeof data === 'object' && Object.keys(data).length === 0)) {
        return (
            <div className="omsk-chart-empty">
                {__('No data available', 'openmost-site-kit')}
            </div>
        );
    }

    // Extract channel data using actual Referrers API field names
    const channels = [];

    const searchVisitors = parseInt(data.Referrers_visitorsFromSearchEngines || 0);
    const socialVisitors = parseInt(data.Referrers_visitorsFromSocialNetworks || 0);
    const directVisitors = parseInt(data.Referrers_visitorsFromDirectEntry || 0);
    const websiteVisitors = parseInt(data.Referrers_visitorsFromWebsites || 0);
    const campaignVisitors = parseInt(data.Referrers_visitorsFromCampaigns || 0);

    if (searchVisitors > 0) channels.push({ name: __('Search Engines', 'openmost-site-kit'), value: searchVisitors });
    if (socialVisitors > 0) channels.push({ name: __('Social Networks', 'openmost-site-kit'), value: socialVisitors });
    if (directVisitors > 0) channels.push({ name: __('Direct Entry', 'openmost-site-kit'), value: directVisitors });
    if (websiteVisitors > 0) channels.push({ name: __('Websites', 'openmost-site-kit'), value: websiteVisitors });
    if (campaignVisitors > 0) channels.push({ name: __('Campaigns', 'openmost-site-kit'), value: campaignVisitors });

    // Check if we have any channel data
    if (channels.length === 0) {
        return (
            <div className="omsk-chart-empty">
                {__('No data available', 'openmost-site-kit')}
            </div>
        );
    }

    const option = {
        tooltip: {
            trigger: 'item',
            formatter: '{b}: {c} ({d}%)',
        },
        legend: {
            orient: 'horizontal',
            bottom: 5,
            left: 'center',
            itemGap: 10,
            textStyle: {
                fontSize: 12,
            },
        },
        grid: {
            containLabel: true,
        },
        series: [
            {
                name: __('Channel Types', 'openmost-site-kit'),
                type: 'pie',
                radius: ['0%', '80%'],
                center: ['50%', '42%'],
                data: channels,
                label: {
                    show: false,
                },
                labelLine: {
                    show: false,
                },
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)',
                    },
                },
            },
        ],
    };

    return (
        <div style={{ width: '100%', height: '400px' }}>
            <ReactECharts
                option={option}
                style={{ height: '100%', width: '100%' }}
                opts={{ renderer: 'svg' }}
            />
        </div>
    );
};

export default ChannelTypesChart;
