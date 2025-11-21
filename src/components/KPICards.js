/**
 * KPI Cards Component
 * Displays key performance indicators: Visits, Page Views, Bounce Rate
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardBody, Spinner } from '@wordpress/components';
import { fetchMatomoData } from '../utils/api';

const KPICards = ({ date, period }) => {
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
            const response = await fetchMatomoData('API.get', {
                period: 'range',
                date,
                showColumns: 'nb_visits,nb_pageviews,bounce_rate',
            });
            setData(response);
        } catch (err) {
            console.error('[KPICards] Error loading data:', err);
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="omsk-kpi-row">
                <Card className="omsk-kpi-card">
                    <CardBody>
                        <div className="omsk-kpi-content omsk-kpi-loading">
                            <Spinner />
                        </div>
                    </CardBody>
                </Card>
                <Card className="omsk-kpi-card">
                    <CardBody>
                        <div className="omsk-kpi-content omsk-kpi-loading">
                            <Spinner />
                        </div>
                    </CardBody>
                </Card>
                <Card className="omsk-kpi-card">
                    <CardBody>
                        <div className="omsk-kpi-content omsk-kpi-loading">
                            <Spinner />
                        </div>
                    </CardBody>
                </Card>
            </div>
        );
    }

    if (error || !data) {
        return null;
    }

    const visits = parseInt(data.nb_visits || 0);
    const pageviews = parseInt(data.nb_pageviews || 0);
    const bounceRate = parseFloat(data.bounce_rate || 0);

    return (
        <div className="omsk-kpi-row">
            <Card className="omsk-kpi-card">
                <CardBody>
                    <div className="omsk-kpi-content">
                        <div className="omsk-kpi-label">{__('Visits', 'openmost-site-kit')}</div>
                        <div className="omsk-kpi-value">{new Intl.NumberFormat().format(visits)}</div>
                    </div>
                </CardBody>
            </Card>

            <Card className="omsk-kpi-card">
                <CardBody>
                    <div className="omsk-kpi-content">
                        <div className="omsk-kpi-label">{__('Page Views', 'openmost-site-kit')}</div>
                        <div className="omsk-kpi-value">{new Intl.NumberFormat().format(pageviews)}</div>
                    </div>
                </CardBody>
            </Card>

            <Card className="omsk-kpi-card">
                <CardBody>
                    <div className="omsk-kpi-content">
                        <div className="omsk-kpi-label">{__('Bounce Rate', 'openmost-site-kit')}</div>
                        <div className="omsk-kpi-value">{bounceRate.toFixed(1)}%</div>
                    </div>
                </CardBody>
            </Card>
        </div>
    );
};

export default KPICards;
