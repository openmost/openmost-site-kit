/**
 * Settings Page Component with Tabs
 */

import { useState, useEffect, useRef, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Card,
    CardBody,
    CardHeader,
    TextControl,
    CheckboxControl,
    SelectControl,
    ToggleControl,
    Button,
    Notice,
    Spinner,
    Flex,
    FlexItem,
    TabPanel,
    ColorPicker,
    Popover,
    __experimentalDivider as Divider,
} from '@wordpress/components';
import { copy, check } from '@wordpress/icons';
import { getSettings, updateSettings, testConnection, getRoles } from '../../utils/api';

/**
 * Color Picker Button Component
 */
const ColorPickerButton = ({ label, color, onChange }) => {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <div style={{ marginBottom: '16px' }}>
            <label style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
                {label}
            </label>
            <Button
                onClick={() => setIsOpen(!isOpen)}
                style={{
                    backgroundColor: `#${color}`,
                    width: '36px',
                    height: '36px',
                    border: '1px solid #8c8f94',
                    borderRadius: '4px',
                    padding: 0,
                    minWidth: '36px',
                }}
            />
            {isOpen && (
                <Popover
                    onClose={() => setIsOpen(false)}
                    position="bottom left"
                >
                    <div style={{ padding: '8px' }}>
                        <ColorPicker
                            color={`#${color}`}
                            onChange={(newColor) => {
                                const hex = newColor.replace('#', '').toUpperCase();
                                onChange(hex);
                            }}
                            enableAlpha={false}
                        />
                    </div>
                </Popover>
            )}
        </div>
    );
};

/**
 * OptOut Preview Component - Uses iframe with optOut action (not optOutJS)
 */
const OptOutPreview = ({ url }) => {
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    // Build iframe URL - convert optOutJS to optOut for iframe embedding
    const iframeUrl = useMemo(() => {
        if (!url) return null;
        try {
            const scriptUrl = new URL(url);
            // Change action from optOutJS to optOut for iframe
            scriptUrl.searchParams.set('action', 'optOut');
            scriptUrl.searchParams.delete('divId');
            return scriptUrl.toString();
        } catch {
            return null;
        }
    }, [url]);

    if (!iframeUrl) {
        return (
            <Notice status="warning" isDismissible={false}>
                {__('Configure Matomo to see the preview', 'openmost-site-kit')}
            </Notice>
        );
    }

    return (
        <div style={{ position: 'relative', minHeight: '120px' }}>
            {isLoading && (
                <div style={{
                    position: 'absolute',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: 'rgba(255,255,255,0.8)',
                    zIndex: 1
                }}>
                    <Spinner />
                </div>
            )}
            {error && (
                <Notice status="error" isDismissible={false}>
                    {error}
                </Notice>
            )}
            <iframe
                src={iframeUrl}
                style={{
                    width: '100%',
                    minHeight: '120px',
                    border: 'none',
                    borderRadius: '4px',
                    background: '#fff',
                }}
                onLoad={() => setIsLoading(false)}
                onError={() => {
                    setError(__('Failed to load preview', 'openmost-site-kit'));
                    setIsLoading(false);
                }}
                title={__('Opt-out Preview', 'openmost-site-kit')}
            />
        </div>
    );
};

/**
 * Form field wrapper with consistent vertical spacing
 */
const FormField = ({ children, marginBottom = '24px' }) => (
    <div style={{ marginBottom }}>
        {children}
    </div>
);

/**
 * General Tab Content - Matomo Configuration
 */
const GeneralTab = ({ settings, onSettingsChange, onSave, saving, notice }) => {
    const handleChange = (field, value) => {
        onSettingsChange({ ...settings, [field]: value });
    };

    const noTrackingEnabled = !settings.enableClassicTracking && !settings.enableMtmTracking;
    const hasBasicConfig = settings.host && settings.idSite;

    return (
        <>
            {notice && (
                <Notice
                    status={notice.type}
                    onRemove={() => {}}
                    isDismissible={false}
                    style={{ marginBottom: '20px' }}
                >
                    {notice.message}
                </Notice>
            )}

            {noTrackingEnabled && hasBasicConfig && (
                <Notice
                    status="error"
                    isDismissible={false}
                    style={{ marginBottom: '20px' }}
                >
                    {__('No tracking method is enabled. Go to the Tracking tab to enable Classic or Tag Manager tracking, otherwise no analytics data will be collected.', 'openmost-site-kit')}
                </Notice>
            )}

            <Card>
                <CardHeader>
                    <h2>{__('Matomo Configuration', 'openmost-site-kit')}</h2>
                </CardHeader>
                <CardBody>
                    <p className="description" style={{ marginBottom: '20px' }}>
                        {__('Configure your Matomo instance connection settings.', 'openmost-site-kit')}
                    </p>

                    <FormField>
                        <TextControl
                            label={
                                <>
                                    {__('Host URL', 'openmost-site-kit')}
                                    <span style={{ color: '#d63638', marginLeft: '4px' }}>*</span>
                                </>
                            }
                            value={settings.host}
                            onChange={(value) => handleChange('host', value)}
                            type="url"
                            placeholder="https://example.matomo.cloud"
                            help={__('Your Matomo instance URL (e.g., https://matomo.example.com or https://example.matomo.cloud)', 'openmost-site-kit')}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </FormField>

                    <FormField>
                        <TextControl
                            label={
                                <>
                                    {__('Site ID', 'openmost-site-kit')}
                                    <span style={{ color: '#d63638', marginLeft: '4px' }}>*</span>
                                </>
                            }
                            value={settings.idSite}
                            onChange={(value) => handleChange('idSite', value)}
                            type="number"
                            min="1"
                            placeholder="1"
                            help={__('Your site ID in Matomo. Find this in Matomo under Administration > Websites > Manage.', 'openmost-site-kit')}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </FormField>

                    <FormField marginBottom="0">
                        <TextControl
                            label={
                                <>
                                    {__('Container ID', 'openmost-site-kit')}
                                    <span style={{ color: '#dba617', marginLeft: '4px', fontSize: '12px', fontWeight: 'normal' }}>
                                        ({__('recommended', 'openmost-site-kit')})
                                    </span>
                                </>
                            }
                            value={settings.idContainer}
                            onChange={(value) => handleChange('idContainer', value)}
                            placeholder="abc123xy"
                            help={__('Tag Manager container ID. Required if using Tag Manager tracking method.', 'openmost-site-kit')}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </FormField>
                </CardBody>
            </Card>

            <div style={{ marginTop: '20px' }}>
                <Button
                    variant="primary"
                    onClick={onSave}
                    isBusy={saving}
                    disabled={saving || !settings.host || !settings.idSite}
                >
                    {saving ? __('Saving...', 'openmost-site-kit') : __('Save Settings', 'openmost-site-kit')}
                </Button>
            </div>
        </>
    );
};

/**
 * Tracking Method Selector Component
 */
const TrackingMethodSelector = ({ value, onChange }) => {
    const methods = [
        {
            id: 'none',
            title: __('Disabled', 'openmost-site-kit'),
            description: __('No tracking code injected', 'openmost-site-kit'),
            icon: (
                <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8 0-1.85.63-3.55 1.69-4.9L16.9 18.31C15.55 19.37 13.85 20 12 20zm6.31-3.1L7.1 5.69C8.45 4.63 10.15 4 12 4c4.42 0 8 3.58 8 8 0 1.85-.63 3.55-1.69 4.9z"/>
                </svg>
            ),
        },
        {
            id: 'classic',
            title: __('Classic Tracking', 'openmost-site-kit'),
            description: __('Standard Matomo JavaScript tracker', 'openmost-site-kit'),
            icon: (
                <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                    <path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/>
                </svg>
            ),
        },
        {
            id: 'mtm',
            title: __('Tag Manager', 'openmost-site-kit'),
            description: __('Matomo Tag Manager (MTM)', 'openmost-site-kit'),
            badge: __('Recommended', 'openmost-site-kit'),
            icon: (
                <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                    <path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16zM16 17H5V7h11l3.55 5L16 17z"/>
                </svg>
            ),
        },
    ];

    return (
        <div className="omsk-tracking-selector">
            {methods.map((method) => (
                <div
                    key={method.id}
                    className={`omsk-tracking-option ${value === method.id ? 'selected' : ''}`}
                    onClick={() => onChange(method.id)}
                    role="button"
                    tabIndex={0}
                    onKeyPress={(e) => e.key === 'Enter' && onChange(method.id)}
                >
                    <div className="omsk-tracking-option-icon">{method.icon}</div>
                    <div className="omsk-tracking-option-content">
                        <div className="omsk-tracking-option-header">
                            <span className="omsk-tracking-option-title">{method.title}</span>
                            {method.badge && (
                                <span className="omsk-tracking-option-badge">{method.badge}</span>
                            )}
                        </div>
                        <span className="omsk-tracking-option-description">{method.description}</span>
                    </div>
                    <div className="omsk-tracking-option-radio">
                        <div className={`omsk-radio ${value === method.id ? 'checked' : ''}`} />
                    </div>
                </div>
            ))}
        </div>
    );
};

/**
 * Tracking Tab Content
 */
const TrackingTab = ({ settings, roles, onSettingsChange, onSave, saving, notice }) => {
    const handleChange = (field, value) => {
        onSettingsChange({ ...settings, [field]: value });
    };

    const handleRoleToggle = (roleKey, checked) => {
        const excludedRoles = settings.excludedRoles || [];
        if (checked) {
            onSettingsChange({ ...settings, excludedRoles: [...excludedRoles, roleKey] });
        } else {
            onSettingsChange({ ...settings, excludedRoles: excludedRoles.filter(r => r !== roleKey) });
        }
    };

    // Determine current tracking method
    const getTrackingMethod = () => {
        if (settings.enableMtmTracking) return 'mtm';
        if (settings.enableClassicTracking) return 'classic';
        return 'none';
    };

    const handleTrackingMethodChange = (method) => {
        onSettingsChange({
            ...settings,
            enableClassicTracking: method === 'classic',
            enableMtmTracking: method === 'mtm',
        });
    };

    const trackingMethod = getTrackingMethod();
    const consentModeEnabled = settings.consentMode && settings.consentMode !== 'disabled';

    return (
        <>
            {notice && (
                <Notice
                    status={notice.type}
                    onRemove={() => {}}
                    isDismissible={false}
                    style={{ marginBottom: '20px' }}
                >
                    {notice.message}
                </Notice>
            )}

            <Card>
                <CardHeader>
                    <h2>{__('Tracking Method', 'openmost-site-kit')}</h2>
                </CardHeader>
                <CardBody>
                    <p className="description" style={{ marginBottom: '20px' }}>
                        {__('Choose how you want to inject Matomo tracking code on your site.', 'openmost-site-kit')}
                    </p>

                    <TrackingMethodSelector
                        value={trackingMethod}
                        onChange={handleTrackingMethodChange}
                    />
                </CardBody>
            </Card>

            {/* Classic tracking options */}
            {trackingMethod === 'classic' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Classic Tracking Options', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <h3>{__('Consent Mode (GDPR)', 'openmost-site-kit')}</h3>
                        <p className="description" style={{ marginBottom: '15px' }}>
                            {__('Configure how the tracking code handles user consent for GDPR compliance.', 'openmost-site-kit')}
                        </p>

                        <FormField>
                            <SelectControl
                                label={__('Consent Mode', 'openmost-site-kit')}
                                value={settings.consentMode || 'disabled'}
                                onChange={(value) => handleChange('consentMode', value)}
                                options={[
                                    { label: __('Disabled - Track all visitors', 'openmost-site-kit'), value: 'disabled' },
                                    { label: __('Require Consent - Wait for consent before tracking', 'openmost-site-kit'), value: 'require_consent' },
                                    { label: __('Require Cookie Consent - Wait for cookie consent', 'openmost-site-kit'), value: 'require_cookie_consent' },
                                ]}
                                help={__('Select how to handle user consent before tracking', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                        </FormField>

                        {consentModeEnabled && (
                            <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                                <p><strong>{__('How to use consent mode:', 'openmost-site-kit')}</strong></p>
                                <p>{__('When consent is given, call:', 'openmost-site-kit')}</p>
                                <code style={{ display: 'block', padding: '10px', backgroundColor: '#f6f7f7', marginTop: '10px', borderRadius: '4px' }}>
                                    {settings.consentMode === 'require_cookie_consent'
                                        ? "_paq.push(['rememberCookieConsentGiven']);"
                                        : "_paq.push(['rememberConsentGiven']);"}
                                </code>
                            </Notice>
                        )}

                        <Divider style={{ marginTop: '20px', marginBottom: '20px' }} />

                        <h3>{__('Accurate Time Measurement', 'openmost-site-kit')}</h3>
                        <p className="description" style={{ marginBottom: '15px' }}>
                            {__('Enable heartbeat timer to accurately measure time spent on page.', 'openmost-site-kit')}
                        </p>

                        <FormField>
                            <ToggleControl
                                label={__('Enable Heartbeat Timer', 'openmost-site-kit')}
                                checked={settings.enableHeartBeatTimer || false}
                                onChange={(value) => handleChange('enableHeartBeatTimer', value)}
                                help={__('Sends periodic pings to Matomo to track actual time on page.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableHeartBeatTimer && (
                            <FormField>
                                <TextControl
                                    label={__('Heartbeat Delay (seconds)', 'openmost-site-kit')}
                                    type="number"
                                    min="5"
                                    max="60"
                                    value={settings.heartBeatTimerDelay || 15}
                                    onChange={(value) => handleChange('heartBeatTimerDelay', parseInt(value) || 15)}
                                    help={__('Time between heartbeat pings (default: 15 seconds)', 'openmost-site-kit')}
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                />
                            </FormField>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Tag Manager options */}
            {trackingMethod === 'mtm' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Tag Manager Options', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        {/* Container ID missing warning with inline form */}
                        {!settings.idContainer && (
                            <Notice status="warning" isDismissible={false} style={{ marginBottom: '20px' }}>
                                <p style={{ marginBottom: '12px' }}>
                                    <strong>{__('Container ID Required', 'openmost-site-kit')}</strong><br />
                                    {__('Tag Manager tracking requires a Container ID to function. Please enter your Container ID below or configure it in the General tab.', 'openmost-site-kit')}
                                </p>
                                <div style={{ display: 'flex', gap: '10px', alignItems: 'flex-end' }}>
                                    <TextControl
                                        label={__('Container ID', 'openmost-site-kit')}
                                        value={settings.idContainer || ''}
                                        onChange={(value) => handleChange('idContainer', value)}
                                        placeholder="abc123xy"
                                        style={{ marginBottom: 0 }}
                                        __nextHasNoMarginBottom
                                        __next40pxDefaultSize
                                    />
                                </div>
                            </Notice>
                        )}

                        <p className="description" style={{ marginBottom: '15px' }}>
                            {__('GDPR consent options are managed directly in the Matomo Tag Manager UI.', 'openmost-site-kit')}
                        </p>

                        <FormField>
                            <ToggleControl
                                label={__('Push context to dataLayer', 'openmost-site-kit')}
                                checked={settings.enableMtmDataLayer !== false}
                                onChange={(value) => handleChange('enableMtmDataLayer', value)}
                                help={__('Adds Matomo configuration to the dataLayer for use in MTM triggers.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                            <p><strong>{__('Available dataLayer variables:', 'openmost-site-kit')}</strong></p>
                            <ul style={{ marginLeft: '20px', marginTop: '10px' }}>
                                <li><code>matomo.host</code> - {__('Your Matomo instance URL', 'openmost-site-kit')}</li>
                                <li><code>matomo.site_id</code> - {__('Your site ID', 'openmost-site-kit')}</li>
                                <li><code>matomo.container_id</code> - {__('Your container ID', 'openmost-site-kit')}</li>
                                <li><code>wordpress.environment</code> - {__('WordPress environment', 'openmost-site-kit')}</li>
                                <li><code>wordpress.user_id</code> - {__('SHA256 hashed user email (if enabled)', 'openmost-site-kit')}</li>
                            </ul>
                        </Notice>
                    </CardBody>
                </Card>
            )}

            {/* User ID Tracking - available for both methods */}
            {trackingMethod !== 'none' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('User ID Tracking', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <FormField>
                            <ToggleControl
                                label={__('Enable User ID tracking', 'openmost-site-kit')}
                                checked={settings.enableUserIdTracking || false}
                                onChange={(value) => handleChange('enableUserIdTracking', value)}
                                help={__('Track logged-in users with a SHA256 hash of their email address.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableUserIdTracking && (
                            <Notice status="warning" isDismissible={false} style={{ marginTop: '15px' }}>
                                <p><strong>{__('Cookie consent required', 'openmost-site-kit')}</strong></p>
                                <p>
                                    {__('User ID tracking requires cookie consent from your visitors. Ensure your consent management solution is configured to obtain consent before enabling this feature.', 'openmost-site-kit')}
                                </p>
                                {trackingMethod === 'mtm' && (
                                    <p style={{ marginTop: '10px' }}>
                                        {__('The user ID will be available in the dataLayer as', 'openmost-site-kit')} <code>wordpress.user_id</code>.
                                    </p>
                                )}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Exclude by Role */}
            {trackingMethod !== 'none' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Exclude Tracking by User Role', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <p className="description" style={{ marginBottom: '15px' }}>
                            {__('Select user roles that should not be tracked.', 'openmost-site-kit')}
                        </p>

                        {roles.length > 0 ? (
                            <div style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: '8px',
                                padding: '15px',
                                backgroundColor: '#f0f0f1',
                                borderRadius: '4px'
                            }}>
                                {roles.map((role) => (
                                    <CheckboxControl
                                        key={role.key}
                                        label={role.name}
                                        checked={(settings.excludedRoles || []).includes(role.key)}
                                        onChange={(checked) => handleRoleToggle(role.key, checked)}
                                        __nextHasNoMarginBottom
                                    />
                                ))}
                            </div>
                        ) : (
                            <Spinner />
                        )}
                    </CardBody>
                </Card>
            )}

            <div style={{ marginTop: '20px' }}>
                <Button
                    variant="primary"
                    onClick={onSave}
                    isBusy={saving}
                    disabled={saving}
                >
                    {saving ? __('Saving...', 'openmost-site-kit') : __('Save Settings', 'openmost-site-kit')}
                </Button>
            </div>
        </>
    );
};

/**
 * Dashboard Tab Content - API Configuration
 */
const DashboardTab = ({ settings, onSettingsChange, onSave, onTestConnection, saving, testing, notice }) => {
    const handleChange = (field, value) => {
        onSettingsChange({ ...settings, [field]: value });
    };

    const hasBasicConfig = settings.host && settings.idSite;

    return (
        <>
            {notice && (
                <Notice
                    status={notice.type}
                    onRemove={() => {}}
                    isDismissible={false}
                    style={{ marginBottom: '20px' }}
                >
                    {notice.message}
                </Notice>
            )}

            {!hasBasicConfig && (
                <Notice status="warning" isDismissible={false} style={{ marginBottom: '20px' }}>
                    {__('Please configure your Matomo Host URL and Site ID in the General tab first.', 'openmost-site-kit')}
                </Notice>
            )}

            <Card>
                <CardHeader>
                    <h2>{__('API Configuration', 'openmost-site-kit')}</h2>
                </CardHeader>
                <CardBody>
                    <p className="description" style={{ marginBottom: '20px' }}>
                        {__('Add your API token to enable the analytics dashboard and access your Matomo data.', 'openmost-site-kit')}
                    </p>

                    <FormField marginBottom="0">
                        <TextControl
                            label={__('Auth Token', 'openmost-site-kit')}
                            value={settings.tokenAuth}
                            onChange={(value) => handleChange('tokenAuth', value)}
                            type="password"
                            disabled={!hasBasicConfig}
                            help={__('Find this in Matomo under Administration > Personal > Security > Auth tokens.', 'openmost-site-kit')}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </FormField>

                    <Flex style={{ marginTop: '24px' }}>
                        <FlexItem>
                            <Button
                                variant="secondary"
                                onClick={onTestConnection}
                                isBusy={testing}
                                disabled={!hasBasicConfig || saving}
                            >
                                {testing ? __('Testing...', 'openmost-site-kit') : __('Test Connection', 'openmost-site-kit')}
                            </Button>
                        </FlexItem>
                    </Flex>
                </CardBody>
            </Card>

            <div style={{ marginTop: '20px' }}>
                <Button
                    variant="primary"
                    onClick={onSave}
                    isBusy={saving}
                    disabled={saving || testing || !hasBasicConfig}
                >
                    {saving ? __('Saving...', 'openmost-site-kit') : __('Save Settings', 'openmost-site-kit')}
                </Button>
            </div>
        </>
    );
};

/**
 * Privacy Tab Content
 */
const PrivacyTab = ({ settings }) => {
    const [copied, setCopied] = useState(false);
    const [debouncedUrl, setDebouncedUrl] = useState('');
    const [config, setConfig] = useState({
        language: 'auto',
        showIntro: true,
        customDesign: false,
        backgroundColor: 'FFFFFF',
        fontColor: '000000',
        fontSize: '14px',
        fontFamily: 'Arial',
    });

    useEffect(() => {
        if (!settings?.host) return;

        const timer = setTimeout(() => {
            const url = new URL(`${settings.host}/index.php`);
            url.searchParams.set('module', 'CoreAdminHome');
            url.searchParams.set('action', 'optOutJS');
            url.searchParams.set('language', config.language);
            url.searchParams.set('showIntro', config.showIntro ? '1' : '0');
            if (config.customDesign) {
                url.searchParams.set('backgroundColor', config.backgroundColor);
                url.searchParams.set('fontColor', config.fontColor);
                url.searchParams.set('fontSize', config.fontSize);
                url.searchParams.set('fontFamily', config.fontFamily);
            }
            setDebouncedUrl(url.toString());
        }, 500);

        return () => clearTimeout(timer);
    }, [config, settings]);

    const buildShortcode = () => {
        const parts = ['[matomo_opt_out'];
        if (config.language !== 'auto') parts.push(` language="${config.language}"`);
        if (!config.showIntro) parts.push(' show_intro="0"');
        if (config.customDesign) {
            if (config.backgroundColor !== 'FFFFFF') parts.push(` background_color="${config.backgroundColor}"`);
            if (config.fontColor !== '000000') parts.push(` font_color="${config.fontColor}"`);
            if (config.fontSize !== '14px') parts.push(` font_size="${config.fontSize}"`);
            if (config.fontFamily !== 'Arial') parts.push(` font_family="${config.fontFamily}"`);
        }
        parts.push(']');
        return parts.join('');
    };

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(buildShortcode());
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
    };

    const isConfigured = settings?.host;
    const shortcode = buildShortcode();

    return (
        <>
            {!isConfigured && (
                <Notice status="warning" isDismissible={false} style={{ marginBottom: '20px' }}>
                    {__('Matomo is not configured. Please configure your Matomo instance in the General tab first.', 'openmost-site-kit')}
                </Notice>
            )}

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                <Card>
                    <CardHeader>
                        <h2>{__('Shortcode Builder', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <p className="description" style={{ marginBottom: '20px' }}>
                            {__('Configure your opt-out form and generate the shortcode to add to your privacy policy page.', 'openmost-site-kit')}
                        </p>

                        <FormField>
                            <SelectControl
                                label={__('Language', 'openmost-site-kit')}
                                value={config.language}
                                onChange={(value) => setConfig({ ...config, language: value })}
                                options={[
                                    { label: __('Auto (detect from Matomo)', 'openmost-site-kit'), value: 'auto' },
                                    { label: __('English', 'openmost-site-kit'), value: 'en' },
                                    { label: __('French', 'openmost-site-kit'), value: 'fr' },
                                    { label: __('German', 'openmost-site-kit'), value: 'de' },
                                    { label: __('Spanish', 'openmost-site-kit'), value: 'es' },
                                    { label: __('Italian', 'openmost-site-kit'), value: 'it' },
                                    { label: __('Portuguese', 'openmost-site-kit'), value: 'pt' },
                                    { label: __('Dutch', 'openmost-site-kit'), value: 'nl' },
                                ]}
                                help={__('Language for the opt-out form text', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                        </FormField>

                        <FormField>
                            <ToggleControl
                                label={__('Show introduction text', 'openmost-site-kit')}
                                checked={config.showIntro}
                                onChange={(value) => setConfig({ ...config, showIntro: value })}
                                help={__('Display explanatory text before the opt-out checkbox', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        <FormField>
                            <ToggleControl
                                label={__('Custom design', 'openmost-site-kit')}
                                checked={config.customDesign}
                                onChange={(value) => setConfig({ ...config, customDesign: value })}
                                help={__('Customize colors and fonts of the opt-out form', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {config.customDesign && (
                            <div style={{
                                marginTop: '16px',
                                padding: '16px',
                                backgroundColor: '#f9f9f9',
                                borderRadius: '4px',
                                border: '1px solid #e0e0e0'
                            }}>
                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginBottom: '16px' }}>
                                    <ColorPickerButton
                                        label={__('Background Color', 'openmost-site-kit')}
                                        color={config.backgroundColor}
                                        onChange={(value) => setConfig({ ...config, backgroundColor: value })}
                                    />
                                    <ColorPickerButton
                                        label={__('Font Color', 'openmost-site-kit')}
                                        color={config.fontColor}
                                        onChange={(value) => setConfig({ ...config, fontColor: value })}
                                    />
                                </div>

                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                                    <TextControl
                                        label={__('Font Size', 'openmost-site-kit')}
                                        value={config.fontSize}
                                        onChange={(value) => setConfig({ ...config, fontSize: value })}
                                        placeholder="14px"
                                        help={__('e.g., 12px, 14px, 1rem', 'openmost-site-kit')}
                                        __nextHasNoMarginBottom
                                        __next40pxDefaultSize
                                    />
                                    <TextControl
                                        label={__('Font Family', 'openmost-site-kit')}
                                        value={config.fontFamily}
                                        onChange={(value) => setConfig({ ...config, fontFamily: value })}
                                        placeholder="Arial"
                                        help={__('e.g., Arial, Helvetica, inherit', 'openmost-site-kit')}
                                        __nextHasNoMarginBottom
                                        __next40pxDefaultSize
                                    />
                                </div>
                            </div>
                        )}

                        <Divider style={{ marginTop: '20px' }} />

                        <div style={{ marginTop: '20px', padding: '15px', backgroundColor: '#f0f0f1', borderRadius: '4px' }}>
                            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '10px' }}>
                                <strong>{__('Generated Shortcode:', 'openmost-site-kit')}</strong>
                                <Button
                                    icon={copied ? check : copy}
                                    iconSize={16}
                                    label={copied ? __('Copied!', 'openmost-site-kit') : __('Copy to clipboard', 'openmost-site-kit')}
                                    onClick={handleCopy}
                                    variant="secondary"
                                    size="small"
                                >
                                    {copied ? __('Copied!', 'openmost-site-kit') : __('Copy', 'openmost-site-kit')}
                                </Button>
                            </div>
                            <code style={{
                                display: 'block',
                                padding: '10px',
                                backgroundColor: 'white',
                                borderRadius: '4px',
                                fontFamily: 'monospace',
                                fontSize: '13px',
                                wordBreak: 'break-all',
                            }}>
                                {shortcode}
                            </code>
                        </div>

                        <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                            {__('Copy this shortcode and paste it onto your cookie privacy policy page.', 'openmost-site-kit')}
                        </Notice>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>
                        <h2>{__('Live Preview', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        {isConfigured ? (
                            <>
                                <p className="description" style={{ marginBottom: '20px' }}>
                                    {__('This is how the opt-out form will appear on your site:', 'openmost-site-kit')}
                                </p>

                                <div style={{
                                    border: '1px solid #ddd',
                                    borderRadius: '4px',
                                    padding: '20px',
                                    backgroundColor: config.customDesign ? `#${config.backgroundColor}` : '#ffffff',
                                }}>
                                    <OptOutPreview url={debouncedUrl} />
                                </div>

                                <Notice status="success" isDismissible={false} style={{ marginTop: '15px' }}>
                                    {__('The preview above is fully functional. You can test the opt-out functionality here.', 'openmost-site-kit')}
                                </Notice>
                            </>
                        ) : (
                            <Notice status="warning" isDismissible={false}>
                                {__('Configure Matomo in the General tab to see a live preview.', 'openmost-site-kit')}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            </div>

            <Card style={{ marginTop: '20px' }}>
                <CardHeader>
                    <h2>{__('Shortcode Parameters', 'openmost-site-kit')}</h2>
                </CardHeader>
                <CardBody>
                    <table className="widefat" style={{ marginTop: '10px' }}>
                        <thead>
                            <tr>
                                <th>{__('Parameter', 'openmost-site-kit')}</th>
                                <th>{__('Description', 'openmost-site-kit')}</th>
                                <th>{__('Default', 'openmost-site-kit')}</th>
                                <th>{__('Example', 'openmost-site-kit')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>language</code></td>
                                <td>{__('Form language (en, fr, de, es, etc.)', 'openmost-site-kit')}</td>
                                <td><code>auto</code></td>
                                <td><code>language="fr"</code></td>
                            </tr>
                            <tr>
                                <td><code>show_intro</code></td>
                                <td>{__('Show introduction text', 'openmost-site-kit')}</td>
                                <td><code>1</code></td>
                                <td><code>show_intro="0"</code></td>
                            </tr>
                            <tr>
                                <td><code>background_color</code></td>
                                <td>{__('Background color (hex without #)', 'openmost-site-kit')}</td>
                                <td><code>FFFFFF</code></td>
                                <td><code>background_color="F5F5F5"</code></td>
                            </tr>
                            <tr>
                                <td><code>font_color</code></td>
                                <td>{__('Text color (hex without #)', 'openmost-site-kit')}</td>
                                <td><code>000000</code></td>
                                <td><code>font_color="333333"</code></td>
                            </tr>
                        </tbody>
                    </table>
                </CardBody>
            </Card>
        </>
    );
};

/**
 * Main Settings Component
 */
const Settings = () => {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [testing, setTesting] = useState(false);
    const [notice, setNotice] = useState(null);
    const [roles, setRoles] = useState([]);
    const [settings, setSettings] = useState({
        host: '',
        idSite: '',
        idContainer: '',
        tokenAuth: '',
        enableClassicTracking: false,
        enableMtmTracking: false,
        enableMtmDataLayer: true,
        excludedRoles: [],
        consentMode: 'disabled',
    });

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            const [settingsData, rolesData] = await Promise.all([
                getSettings(),
                getRoles(),
            ]);
            setSettings({ ...settings, ...settingsData });
            setRoles(rolesData);
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

    if (loading) {
        return (
            <div style={{ padding: '20px', textAlign: 'center' }}>
                <Spinner />
            </div>
        );
    }

    const hasBasicConfig = settings.host && settings.idSite;

    const tabs = [
        {
            name: 'general',
            title: __('General', 'openmost-site-kit'),
            className: 'omsk-tab-general',
        },
        // Only show other tabs if host and site ID are configured
        ...(hasBasicConfig ? [
            {
                name: 'tracking',
                title: __('Tracking', 'openmost-site-kit'),
                className: 'omsk-tab-tracking',
            },
            {
                name: 'dashboard',
                title: __('Dashboard', 'openmost-site-kit'),
                className: 'omsk-tab-dashboard',
            },
            {
                name: 'privacy',
                title: __('Privacy', 'openmost-site-kit'),
                className: 'omsk-tab-privacy',
            },
        ] : []),
    ];

    return (
        <div className="omsk-settings">
            <h1>{__('Settings', 'openmost-site-kit')}</h1>

            <TabPanel
                className="omsk-settings-tabs"
                activeClass="is-active"
                tabs={tabs}
            >
                {(tab) => (
                    <div className="omsk-tab-content" style={{ marginTop: '20px' }}>
                        {tab.name === 'general' && (
                            <GeneralTab
                                settings={settings}
                                onSettingsChange={setSettings}
                                onSave={handleSave}
                                saving={saving}
                                notice={notice}
                            />
                        )}
                        {tab.name === 'tracking' && (
                            <TrackingTab
                                settings={settings}
                                roles={roles}
                                onSettingsChange={setSettings}
                                onSave={handleSave}
                                saving={saving}
                                notice={notice}
                            />
                        )}
                        {tab.name === 'dashboard' && (
                            <DashboardTab
                                settings={settings}
                                onSettingsChange={setSettings}
                                onSave={handleSave}
                                onTestConnection={handleTestConnection}
                                saving={saving}
                                testing={testing}
                                notice={notice}
                            />
                        )}
                        {tab.name === 'privacy' && (
                            <PrivacyTab settings={settings} />
                        )}
                    </div>
                )}
            </TabPanel>
        </div>
    );
};

export default Settings;
