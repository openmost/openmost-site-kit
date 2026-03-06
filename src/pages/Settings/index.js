/**
 * Settings Page Component with Tabs
 */

import { useState, useEffect, useRef } from '@wordpress/element';
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
import { getSettings, updateSettings, testConnection, getRoles, getPostTypes } from '../../utils/api';

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
 * OptOut Preview Component - Uses script-based approach (optOutJS)
 * Dynamically loads Matomo opt-out script with proper refresh on URL change
 */
const OptOutPreview = ({ url }) => {
    const containerRef = useRef(null);
    const scriptRef = useRef(null);
    const [isLoading, setIsLoading] = useState(true);
    // Generate a stable ID that changes when URL changes
    const [containerId, setContainerId] = useState(() => `matomo-opt-out-settings-preview-${Date.now()}`);

    useEffect(() => {
        if (!url || !containerRef.current) return;

        // Generate new unique ID for this URL change
        const newContainerId = `matomo-opt-out-settings-preview-${Date.now()}`;
        setContainerId(newContainerId);
        setIsLoading(true);

        // Remove previous script if exists
        if (scriptRef.current && scriptRef.current.parentNode) {
            scriptRef.current.parentNode.removeChild(scriptRef.current);
            scriptRef.current = null;
        }

        // Clear container content manually (outside React's control)
        while (containerRef.current.firstChild) {
            containerRef.current.removeChild(containerRef.current.firstChild);
        }

        // Set the ID on the container BEFORE loading the script
        containerRef.current.id = newContainerId;

        // Parse URL and update the divId parameter
        const scriptUrl = new URL(url);
        scriptUrl.searchParams.set('divId', newContainerId);

        // Small delay to ensure DOM is updated before script runs
        setTimeout(() => {
            // Create and load script
            const script = document.createElement('script');
            script.src = scriptUrl.toString();
            script.async = true;
            script.onload = () => setIsLoading(false);
            script.onerror = () => setIsLoading(false);
            scriptRef.current = script;

            document.body.appendChild(script);
        }, 50);

        // Cleanup on unmount or before next effect
        return () => {
            if (scriptRef.current && scriptRef.current.parentNode) {
                scriptRef.current.parentNode.removeChild(scriptRef.current);
                scriptRef.current = null;
            }
        };
    }, [url]);

    if (!url) {
        return (
            <Notice status="warning" isDismissible={false}>
                {__('Configure Matomo to see the preview', 'openmost-site-kit')}
            </Notice>
        );
    }

    return (
        <div style={{ position: 'relative', minHeight: '100px' }}>
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
            {/* This div is controlled entirely by Matomo - NO React children */}
            <div ref={containerRef} id={containerId} />
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
            description: __('Matomo Tag Manager', 'openmost-site-kit'),
            badge: __('Recommended', 'openmost-site-kit'),
            icon: (
                <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                    <path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16zM16 17H5V7h11l3.55 5L16 17z"/>
                </svg>
            ),
        },
        {
            id: 'server',
            title: __('Server-Side', 'openmost-site-kit'),
            description: __('PHP tracking (ad-blocker resistant)', 'openmost-site-kit'),
            icon: (
                <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                    <path d="M2 5c0-1.1.9-2 2-2h16c1.1 0 2 .9 2 2v4c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V5zm2 0v4h16V5H4zm-2 8c0-1.1.9-2 2-2h16c1.1 0 2 .9 2 2v4c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2v-4zm2 0v4h16v-4H4zm13 1.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zM17 6a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/>
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
        if (settings.enableServerTracking) return 'server';
        if (settings.enableMtmTracking) return 'mtm';
        if (settings.enableClassicTracking) return 'classic';
        return 'none';
    };

    const handleTrackingMethodChange = (method) => {
        onSettingsChange({
            ...settings,
            enableClassicTracking: method === 'classic',
            enableMtmTracking: method === 'mtm',
            enableServerTracking: method === 'server',
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
                        <h2>{__('Classic Tracking Configuration', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <p className="description" style={{ marginBottom: '20px' }}>
                            {__('Configure the required settings for Classic Matomo tracking.', 'openmost-site-kit')}
                        </p>

                        <FormField>
                            <TextControl
                                label={
                                    <>
                                        {__('Matomo Host', 'openmost-site-kit')}
                                        <span style={{ color: '#d63638', marginLeft: '4px' }}>*</span>
                                    </>
                                }
                                value={settings.host || ''}
                                onChange={(value) => handleChange('host', value)}
                                placeholder="https://example.matomo.cloud"
                                help={__('Your Matomo instance URL', 'openmost-site-kit')}
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
                                type="number"
                                value={settings.idSite || ''}
                                onChange={(value) => handleChange('idSite', value)}
                                placeholder="1"
                                help={__('Your site ID in Matomo', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                        </FormField>

                        {(!settings.host || !settings.idSite) && (
                            <Notice status="warning" isDismissible={false}>
                                {__('Both Matomo Host and Site ID are required for Classic tracking to work.', 'openmost-site-kit')}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Classic tracking - Consent Mode (GDPR) */}
            {trackingMethod === 'classic' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Consent Mode', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
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
                                help={__('Configure how the tracking code handles user consent for privacy compliance.', 'openmost-site-kit')}
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
                    </CardBody>
                </Card>
            )}

            {/* Classic tracking - Heartbeat Timer */}
            {trackingMethod === 'classic' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Heartbeat Timer', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <FormField>
                            <ToggleControl
                                label={__('Enable Heartbeat Timer', 'openmost-site-kit')}
                                checked={settings.enableHeartBeatTimer || false}
                                onChange={(value) => handleChange('enableHeartBeatTimer', value)}
                                help={__('Sends periodic pings to Matomo to accurately measure time spent on page.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableHeartBeatTimer && (
                            <FormField style={{ marginTop: '15px' }}>
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

            {/* Classic tracking - Site Search */}
            {trackingMethod === 'classic' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Site Search', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <FormField>
                            <ToggleControl
                                label={__('Enable Site Search Tracking', 'openmost-site-kit')}
                                checked={settings.enableJsSearchTracking || false}
                                onChange={(value) => handleChange('enableJsSearchTracking', value)}
                                help={__('Track internal site searches performed by visitors.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableJsSearchTracking && (
                            <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                                {__('WordPress searches will be automatically tracked with search keyword, category, and result count.', 'openmost-site-kit')}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Classic tracking - WooCommerce Ecommerce */}
            {trackingMethod === 'classic' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('WooCommerce Ecommerce', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <FormField>
                            <ToggleControl
                                label={__('Enable Ecommerce Tracking', 'openmost-site-kit')}
                                checked={settings.enableJsEcommerce || false}
                                onChange={(value) => handleChange('enableJsEcommerce', value)}
                                help={__('Track product views, cart actions, and purchases via _paq commands.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableJsEcommerce && (
                            <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                                {__('Ecommerce events will be tracked using the Matomo JavaScript tracker. Requires WooCommerce to be active.', 'openmost-site-kit')}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Tag Manager options */}
            {trackingMethod === 'mtm' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Tag Manager Configuration', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <p className="description" style={{ marginBottom: '20px' }}>
                            {__('Configure the required settings for Matomo Tag Manager.', 'openmost-site-kit')}
                        </p>

                        <FormField>
                            <TextControl
                                label={
                                    <>
                                        {__('Matomo Host', 'openmost-site-kit')}
                                        <span style={{ color: '#d63638', marginLeft: '4px' }}>*</span>
                                    </>
                                }
                                value={settings.host || ''}
                                onChange={(value) => handleChange('host', value)}
                                placeholder="https://example.matomo.cloud"
                                help={__('Your Matomo instance URL', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                        </FormField>

                        <FormField>
                            <TextControl
                                label={
                                    <>
                                        {__('Container ID', 'openmost-site-kit')}
                                        <span style={{ color: '#d63638', marginLeft: '4px' }}>*</span>
                                    </>
                                }
                                value={settings.idContainer || ''}
                                onChange={(value) => handleChange('idContainer', value)}
                                placeholder="abc123xy"
                                help={__('Your Tag Manager container ID', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                        </FormField>

                        {(!settings.host || !settings.idContainer) && (
                            <Notice status="warning" isDismissible={false} style={{ marginBottom: '20px' }}>
                                {__('Both Matomo Host and Container ID are required for Tag Manager to work.', 'openmost-site-kit')}
                            </Notice>
                        )}

                        <Divider style={{ marginTop: '20px', marginBottom: '20px' }} />

                        <FormField>
                            <ToggleControl
                                label={__('Push context to dataLayer', 'openmost-site-kit')}
                                checked={settings.enableMtmDataLayer !== false}
                                onChange={(value) => handleChange('enableMtmDataLayer', value)}
                                help={__('Adds Matomo configuration to the dataLayer for use in MTM triggers.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableMtmDataLayer !== false && (
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
                        )}

                        <p className="description" style={{ marginTop: '20px' }}>
                            {__('Privacy consent options are managed directly in the Matomo Tag Manager UI.', 'openmost-site-kit')}
                        </p>
                    </CardBody>
                </Card>
            )}

            {/* Tag Manager - Site Search */}
            {trackingMethod === 'mtm' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Site Search', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <FormField>
                            <ToggleControl
                                label={__('Enable Site Search Tracking', 'openmost-site-kit')}
                                checked={settings.enableDataLayerSearchTracking || false}
                                onChange={(value) => handleChange('enableDataLayerSearchTracking', value)}
                                help={__('Push site search events to the _mtm dataLayer.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableDataLayerSearchTracking && (
                            <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                                <p>{__('Search events will be pushed to _mtm with:', 'openmost-site-kit')}</p>
                                <ul style={{ marginLeft: '20px', marginTop: '10px' }}>
                                    <li><code>event: "search"</code></li>
                                    <li><code>search_keyword</code> - {__('Search keyword', 'openmost-site-kit')}</li>
                                    <li><code>search_category</code> - {__('Search category (if applicable)', 'openmost-site-kit')}</li>
                                    <li><code>search_count</code> - {__('Number of results', 'openmost-site-kit')}</li>
                                </ul>
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Tag Manager - WooCommerce Ecommerce */}
            {trackingMethod === 'mtm' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('WooCommerce Ecommerce', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <FormField>
                            <ToggleControl
                                label={__('Enable Ecommerce Tracking', 'openmost-site-kit')}
                                checked={settings.enableDataLayerEcommerce || false}
                                onChange={(value) => handleChange('enableDataLayerEcommerce', value)}
                                help={__('Push product views, cart actions, and purchases to _mtm dataLayer (GA4 format).', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableDataLayerEcommerce && (
                            <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                                {__('Ecommerce events will be pushed to the _mtm dataLayer in GA4 format. Requires WooCommerce to be active.', 'openmost-site-kit')}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Server-Side PHP options */}
            {trackingMethod === 'server' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Server-Side Tracking Configuration', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <p className="description" style={{ marginBottom: '20px' }}>
                            {__('Configure the required settings for Server-Side PHP tracking.', 'openmost-site-kit')}
                        </p>

                        <FormField>
                            <TextControl
                                label={
                                    <>
                                        {__('Matomo Host', 'openmost-site-kit')}
                                        <span style={{ color: '#d63638', marginLeft: '4px' }}>*</span>
                                    </>
                                }
                                value={settings.host || ''}
                                onChange={(value) => handleChange('host', value)}
                                placeholder="https://example.matomo.cloud"
                                help={__('Your Matomo instance URL', 'openmost-site-kit')}
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
                                type="number"
                                value={settings.idSite || ''}
                                onChange={(value) => handleChange('idSite', value)}
                                placeholder="1"
                                help={__('Your site ID in Matomo', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                        </FormField>

                        <FormField>
                            <TextControl
                                label={
                                    <>
                                        {__('Auth Token', 'openmost-site-kit')}
                                        <span style={{ color: '#d63638', marginLeft: '4px' }}>*</span>
                                    </>
                                }
                                type="password"
                                value={settings.tokenAuth || ''}
                                onChange={(value) => handleChange('tokenAuth', value)}
                                placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                help={__('Required for IP geolocation and visitor recognition.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                        </FormField>

                        {(!settings.host || !settings.idSite || !settings.tokenAuth) && (
                            <Notice status="warning" isDismissible={false}>
                                {__('Matomo Host, Site ID, and Auth Token are all required for Server-Side tracking to work correctly.', 'openmost-site-kit')}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Server-Side - Site Search */}
            {trackingMethod === 'server' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Site Search', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <FormField>
                            <ToggleControl
                                label={__('Enable Site Search Tracking', 'openmost-site-kit')}
                                checked={settings.enableServerSearchTracking || false}
                                onChange={(value) => handleChange('enableServerSearchTracking', value)}
                                help={__('Track internal site searches server-side. Ad-blocker resistant.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableServerSearchTracking && (
                            <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                                {__('WordPress searches will be tracked server-side with search keyword, category, and result count using the Matomo PHP tracker.', 'openmost-site-kit')}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Server-Side - WooCommerce Ecommerce */}
            {trackingMethod === 'server' && (
                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('WooCommerce Ecommerce', 'openmost-site-kit')}</h2>
                    </CardHeader>
                    <CardBody>
                        <FormField>
                            <ToggleControl
                                label={__('Enable Ecommerce Tracking', 'openmost-site-kit')}
                                checked={settings.enableServerEcommerce || false}
                                onChange={(value) => handleChange('enableServerEcommerce', value)}
                                help={__('Track product views, cart actions, and purchases via PHP. Ad-blocker resistant.', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </FormField>

                        {settings.enableServerEcommerce && (
                            <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                                {__('Ecommerce events will be tracked server-side using the Matomo PHP tracker. Cannot be blocked by ad blockers. Requires WooCommerce to be active.', 'openmost-site-kit')}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* User ID Tracking - available for all methods */}
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
 * Features Tab Content - Dashboard API & Annotations
 */
const FeaturesTab = ({ settings, postTypes, onSettingsChange, onSave, onTestConnection, saving, testing, notice }) => {
    const handleChange = (field, value) => {
        onSettingsChange({ ...settings, [field]: value });
    };

    const handlePostTypeToggle = (postType, checked) => {
        const annotationPostTypes = settings.annotationPostTypes || [];
        if (checked) {
            onSettingsChange({ ...settings, annotationPostTypes: [...annotationPostTypes, postType] });
        } else {
            onSettingsChange({ ...settings, annotationPostTypes: annotationPostTypes.filter(pt => pt !== postType) });
        }
    };

    const hasBasicConfig = settings.host && settings.idSite;
    const hasTokenAuth = settings.tokenAuth;

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

            {/* Dashboard Card */}
            <Card>
                <CardHeader>
                    <h2>{__('WordPress Dashboard', 'openmost-site-kit')}</h2>
                </CardHeader>
                <CardBody>
                    <p className="description" style={{ marginBottom: '20px' }}>
                        {__('Enable the Matomo analytics dashboard within WordPress admin.', 'openmost-site-kit')}
                    </p>

                    <FormField>
                        <TextControl
                            label={__('Auth Token', 'openmost-site-kit')}
                            value={settings.tokenAuth || ''}
                            onChange={(value) => handleChange('tokenAuth', value)}
                            type="password"
                            help={__('Required to fetch analytics data. Find this in Matomo under Administration > Personal > Security > Auth tokens.', 'openmost-site-kit')}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </FormField>

                    <Flex>
                        <FlexItem>
                            <Button
                                variant="secondary"
                                onClick={onTestConnection}
                                isBusy={testing}
                                disabled={!hasBasicConfig || !hasTokenAuth || saving}
                            >
                                {testing ? __('Testing...', 'openmost-site-kit') : __('Test Connection', 'openmost-site-kit')}
                            </Button>
                        </FlexItem>
                    </Flex>
                </CardBody>
            </Card>

            {/* Automatic Annotations Card */}
            <Card style={{ marginTop: '20px' }}>
                <CardHeader>
                    <h2>{__('Automatic Annotations', 'openmost-site-kit')}</h2>
                </CardHeader>
                <CardBody>
                    <FormField>
                        <ToggleControl
                            label={__('Enable Automatic Annotations', 'openmost-site-kit')}
                            checked={settings.enableAutoAnnotations || false}
                            onChange={(value) => handleChange('enableAutoAnnotations', value)}
                            help={__('Automatically create annotations in Matomo when content is published.', 'openmost-site-kit')}
                            __nextHasNoMarginBottom
                        />
                    </FormField>

                    {settings.enableAutoAnnotations && (
                        <>
                            {!hasTokenAuth && (
                                <Notice status="warning" isDismissible={false} style={{ marginTop: '15px', marginBottom: '15px' }}>
                                    {__('An Auth Token is required for automatic annotations to work.', 'openmost-site-kit')}
                                </Notice>
                            )}

                            <div style={{ marginTop: '20px' }}>
                                <p className="description" style={{ marginBottom: '15px' }}>
                                    {__('Select which post types should create annotations when published:', 'openmost-site-kit')}
                                </p>

                                {postTypes.length > 0 ? (
                                    <div style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '8px',
                                        padding: '15px',
                                        backgroundColor: '#f0f0f1',
                                        borderRadius: '4px'
                                    }}>
                                        {postTypes.map((postType) => (
                                            <CheckboxControl
                                                key={postType.name}
                                                label={postType.label}
                                                checked={(settings.annotationPostTypes || []).includes(postType.name)}
                                                onChange={(checked) => handlePostTypeToggle(postType.name, checked)}
                                                __nextHasNoMarginBottom
                                            />
                                        ))}
                                    </div>
                                ) : (
                                    <Spinner />
                                )}

                                <Divider style={{ marginTop: '20px', marginBottom: '20px' }} />

                                <FormField>
                                    <TextControl
                                        label={__('Annotation Format', 'openmost-site-kit')}
                                        value={settings.annotationFormat || 'New {post_type} published: "{title}"'}
                                        onChange={(value) => handleChange('annotationFormat', value)}
                                        help={__('Customize the annotation message using variables.', 'openmost-site-kit')}
                                        __nextHasNoMarginBottom
                                        __next40pxDefaultSize
                                    />
                                </FormField>

                                <Notice status="info" isDismissible={false} style={{ marginTop: '15px' }}>
                                    <p><strong>{__('Available variables:', 'openmost-site-kit')}</strong></p>
                                    <ul style={{ marginLeft: '20px', marginTop: '10px', marginBottom: '10px' }}>
                                        <li><code>{'{post_type}'}</code> - {__('Post type name (e.g., Post, Page, Product)', 'openmost-site-kit')}</li>
                                        <li><code>{'{title}'}</code> - {__('Post title', 'openmost-site-kit')}</li>
                                        <li><code>{'{url}'}</code> - {__('Post URL', 'openmost-site-kit')}</li>
                                        <li><code>{'{author}'}</code> - {__('Author display name', 'openmost-site-kit')}</li>
                                    </ul>
                                    <p style={{ fontStyle: 'italic' }}>
                                        {__('Example:', 'openmost-site-kit')} {(settings.annotationFormat || 'New {post_type} published: "{title}"')
                                            .replace('{post_type}', 'Post')
                                            .replace('{title}', 'How to boost your SEO')
                                            .replace('{url}', 'https://example.com/how-to-boost-seo')
                                            .replace('{author}', 'John Doe')}
                                    </p>
                                </Notice>
                            </div>
                        </>
                    )}
                </CardBody>
            </Card>

            <div style={{ marginTop: '20px' }}>
                <Button
                    variant="primary"
                    onClick={onSave}
                    isBusy={saving}
                    disabled={saving || testing}
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
    const [postTypes, setPostTypes] = useState([]);
    const [settings, setSettings] = useState({
        host: '',
        idSite: '',
        idContainer: '',
        tokenAuth: '',
        enableClassicTracking: false,
        enableMtmTracking: false,
        enableServerTracking: false,
        enableMtmDataLayer: true,
        excludedRoles: [],
        consentMode: 'disabled',
        enableServerEcommerce: false,
        enableJsEcommerce: false,
        enableDataLayerEcommerce: false,
        enableAutoAnnotations: false,
        annotationPostTypes: [],
        annotationFormat: 'New {post_type} published: "{title}"',
        enableJsSearchTracking: false,
        enableDataLayerSearchTracking: false,
        enableServerSearchTracking: false,
    });

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            const [settingsData, rolesData, postTypesData] = await Promise.all([
                getSettings(),
                getRoles(),
                getPostTypes(),
            ]);
            setSettings({ ...settings, ...settingsData });
            setRoles(rolesData);
            setPostTypes(postTypesData);
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

    // Determine if we have enough config to show additional tabs
    const hasTrackingMethod = settings.enableClassicTracking || settings.enableMtmTracking || settings.enableServerTracking;
    const hasBasicConfig = settings.host && settings.idSite;

    const tabs = [
        {
            name: 'tracking',
            title: __('Tracking', 'openmost-site-kit'),
            className: 'omsk-tab-tracking',
        },
        // Only show other tabs if a tracking method is enabled and configured
        ...(hasTrackingMethod && hasBasicConfig ? [
            {
                name: 'features',
                title: __('Features', 'openmost-site-kit'),
                className: 'omsk-tab-features',
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
                        {tab.name === 'features' && (
                            <FeaturesTab
                                settings={settings}
                                postTypes={postTypes}
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
