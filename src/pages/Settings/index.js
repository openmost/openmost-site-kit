/**
 * Settings Page Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Card,
    CardBody,
    CardHeader,
    TextControl,
    CheckboxControl,
    Button,
    Notice,
    Spinner,
    Flex,
    FlexBlock,
    FlexItem,
} from '@wordpress/components';
import { getSettings, updateSettings, testConnection } from '../../utils/api';

const Settings = () => {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [testing, setTesting] = useState(false);
    const [notice, setNotice] = useState(null);
    const [settings, setSettings] = useState({
        host: '',
        idSite: '',
        idContainer: '',
        tokenAuth: '',
        enableClassicTracking: false,
        enableMtmTracking: false,
    });

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        try {
            const data = await getSettings();
            setSettings(data);
        } catch (error) {
            setNotice({
                type: 'error',
                message: __('Failed to load settings', 'openmost-site-kit'),
            });
        } finally {
            setLoading(false);
        }
    };

    const handleSave = async () => {
        setSaving(true);
        setNotice(null);

        try {
            await updateSettings(settings);
            setNotice({
                type: 'success',
                message: __('Settings saved successfully', 'openmost-site-kit'),
            });
        } catch (error) {
            setNotice({
                type: 'error',
                message: error.message || __('Failed to save settings', 'openmost-site-kit'),
            });
        } finally {
            setSaving(false);
        }
    };

    const handleTestConnection = async () => {
        setTesting(true);
        setNotice(null);

        try {
            const result = await testConnection(
                settings.host,
                settings.idSite,
                settings.tokenAuth
            );
            setNotice({
                type: 'success',
                message: result.message + (result.version ? ` (${__('Version', 'openmost-site-kit')}: ${result.version})` : ''),
            });
        } catch (error) {
            setNotice({
                type: 'error',
                message: error.message || __('Failed to connect to Matomo', 'openmost-site-kit'),
            });
        } finally {
            setTesting(false);
        }
    };

    const handleChange = (field, value) => {
        setSettings({ ...settings, [field]: value });
    };

    const bothTrackingCodesEnabled = settings.enableClassicTracking && settings.enableMtmTracking;

    if (loading) {
        return (
            <div style={{ padding: '20px', textAlign: 'center' }}>
                <Spinner />
            </div>
        );
    }

    return (
        <div className="omsk-settings">
            <h1>{__('Settings', 'openmost-site-kit')}</h1>

            <p className="description">
                {__('Connect your Matomo instance', 'openmost-site-kit')}
            </p>

            {notice && (
                <Notice
                    status={notice.type}
                    onRemove={() => setNotice(null)}
                    isDismissible
                >
                    {notice.message}
                </Notice>
            )}

            {bothTrackingCodesEnabled && (
                <Notice status="error" isDismissible={false}>
                    {__('Both Matomo and Matomo Tag Manager codes are deployed. You should use only one of them.', 'openmost-site-kit')}
                </Notice>
            )}

            <Card>
                <CardHeader>
                    <h2>{__('Matomo Configuration', 'openmost-site-kit')}</h2>
                </CardHeader>
                <CardBody>
                    <TextControl
                        label={__('Host URL', 'openmost-site-kit')}
                        value={settings.host}
                        onChange={(value) => handleChange('host', value)}
                        type="url"
                        required
                        help={__('Your Matomo instance URL (e.g., https://matomo.example.com or https://example.matomo.cloud)', 'openmost-site-kit')}
                    />

                    <TextControl
                        label={__('Site ID', 'openmost-site-kit')}
                        value={settings.idSite}
                        onChange={(value) => handleChange('idSite', value)}
                        type="number"
                        min="1"
                        required
                        help={__('Your site ID in Matomo', 'openmost-site-kit')}
                    />

                    <TextControl
                        label={__('Container ID', 'openmost-site-kit')}
                        value={settings.idContainer}
                        onChange={(value) => handleChange('idContainer', value)}
                        help={__('Tag Manager container ID (optional)', 'openmost-site-kit')}
                    />

                    <TextControl
                        label={__('Auth Token', 'openmost-site-kit')}
                        value={settings.tokenAuth}
                        onChange={(value) => handleChange('tokenAuth', value)}
                        type="password"
                        help={__('API authentication token for dashboard access', 'openmost-site-kit')}
                    />

                    <Flex style={{ marginTop: '20px' }}>
                        <FlexItem>
                            <Button
                                variant="secondary"
                                onClick={handleTestConnection}
                                isBusy={testing}
                                disabled={!settings.host || !settings.idSite || saving}
                            >
                                {testing ? __('Testing...', 'openmost-site-kit') : __('Test Connection', 'openmost-site-kit')}
                            </Button>
                        </FlexItem>
                    </Flex>
                </CardBody>
            </Card>

            <Card style={{ marginTop: '20px' }}>
                <CardHeader>
                    <h2>{__('Tracking Code', 'openmost-site-kit')}</h2>
                </CardHeader>
                <CardBody>
                    <CheckboxControl
                        label={__('Enable classic tracking code', 'openmost-site-kit')}
                        help={__('Not recommended if you use Tag Manager', 'openmost-site-kit')}
                        checked={settings.enableClassicTracking}
                        onChange={(value) => handleChange('enableClassicTracking', value)}
                    />

                    <CheckboxControl
                        label={__('Enable Tag Manager tracking code', 'openmost-site-kit')}
                        help={__('Recommended - provides more flexibility', 'openmost-site-kit')}
                        checked={settings.enableMtmTracking}
                        onChange={(value) => handleChange('enableMtmTracking', value)}
                    />
                </CardBody>
            </Card>

            <div style={{ marginTop: '20px' }}>
                <Button
                    variant="primary"
                    onClick={handleSave}
                    isBusy={saving}
                    disabled={saving || testing}
                >
                    {saving ? __('Saving...', 'openmost-site-kit') : __('Save Settings', 'openmost-site-kit')}
                </Button>
            </div>
        </div>
    );
};

export default Settings;
