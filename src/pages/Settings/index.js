/**
 * Settings Page Component with Tabs
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
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
                                // Remove # from hex
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
 * OptOut Preview Component
 * Dynamically loads Matomo opt-out script
 * Uses a stable container ID and ensures DOM is ready before script loads
 *
 * Key fix: Track mounted state to prevent script operations after unmount
 */
const OptOutPreview = ({ url }) => {
    const containerRef = useRef(null);
    const scriptRef = useRef(null);
    const timerRef = useRef(null);
    const mountedRef = useRef(true);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);
    // Use a stable ID that persists across renders
    const stableId = useRef(`matomo-opt-out-preview-${Math.random().toString(36).substr(2, 9)}`);

    // Track mounted state
    useEffect(() => {
        mountedRef.current = true;
        return () => {
            mountedRef.current = false;
        };
    }, []);

    useEffect(() => {
        if (!url) return;

        const containerId = stableId.current;
        setIsLoading(true);
        setError(null);

        // Clear any pending timer
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }

        // Remove previous script if exists
        if (scriptRef.current && scriptRef.current.parentNode) {
            scriptRef.current.parentNode.removeChild(scriptRef.current);
            scriptRef.current = null;
        }

        // Clear container content
        if (containerRef.current) {
            while (containerRef.current.firstChild) {
                containerRef.current.removeChild(containerRef.current.firstChild);
            }
            // Set the ID immediately
            containerRef.current.id = containerId;
        }

        // Build script URL with our container ID
        const scriptUrl = new URL(url);
        scriptUrl.searchParams.set('divId', containerId);

        // Use setTimeout to ensure DOM has updated with the new ID
        timerRef.current = setTimeout(() => {
            // Check if still mounted
            if (!mountedRef.current) return;

            // Double-check the container exists with the correct ID
            const container = document.getElementById(containerId);
            if (!container) {
                if (mountedRef.current) {
                    setError('Container not ready');
                    setIsLoading(false);
                }
                return;
            }

            // Create and load script
            const script = document.createElement('script');
            script.src = scriptUrl.toString();
            script.async = true;
            script.onload = () => {
                if (mountedRef.current) setIsLoading(false);
            };
            script.onerror = () => {
                if (mountedRef.current) {
                    setError('Failed to load script');
                    setIsLoading(false);
                }
            };
            scriptRef.current = script;

            document.body.appendChild(script);
        }, 150);

        return () => {
            // Clear timer first
            if (timerRef.current) {
                clearTimeout(timerRef.current);
                timerRef.current = null;
            }
            // Remove script
            if (scriptRef.current && scriptRef.current.parentNode) {
                scriptRef.current.parentNode.removeChild(scriptRef.current);
                scriptRef.current = null;
            }
        };
    }, [url]);

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
            {error && (
                <Notice status="error" isDismissible={false}>
                    {error}
                </Notice>
            )}
            <div ref={containerRef} id={stableId.current} />
        </div>
    );
};

/**
 * General Tab Content
 */
const GeneralTab = ({ settings, roles, onSettingsChange, onSave, onTestConnection, saving, testing, notice }) => {
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

    const bothTrackingCodesEnabled = settings.enableClassicTracking && settings.enableMtmTracking;

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

            {bothTrackingCodesEnabled && (
                <Notice status="error" isDismissible={false} style={{ marginBottom: '20px' }}>
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
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />

                    <TextControl
                        label={__('Site ID', 'openmost-site-kit')}
                        value={settings.idSite}
                        onChange={(value) => handleChange('idSite', value)}
                        type="number"
                        min="1"
                        required
                        help={__('Your site ID in Matomo', 'openmost-site-kit')}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />

                    <TextControl
                        label={__('Container ID', 'openmost-site-kit')}
                        value={settings.idContainer}
                        onChange={(value) => handleChange('idContainer', value)}
                        help={__('Tag Manager container ID (optional)', 'openmost-site-kit')}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />

                    <TextControl
                        label={__('Auth Token', 'openmost-site-kit')}
                        value={settings.tokenAuth}
                        onChange={(value) => handleChange('tokenAuth', value)}
                        type="password"
                        help={__('API authentication token for dashboard access', 'openmost-site-kit')}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />

                    <Flex style={{ marginTop: '20px' }}>
                        <FlexItem>
                            <Button
                                variant="secondary"
                                onClick={onTestConnection}
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
                        __nextHasNoMarginBottom
                    />

                    <CheckboxControl
                        label={__('Enable Tag Manager tracking code', 'openmost-site-kit')}
                        help={__('Recommended - provides more flexibility', 'openmost-site-kit')}
                        checked={settings.enableMtmTracking}
                        onChange={(value) => handleChange('enableMtmTracking', value)}
                        __nextHasNoMarginBottom
                    />

                    <Divider style={{ marginTop: '20px', marginBottom: '20px' }} />

                    <h3>{__('Exclude Tracking by User Role', 'openmost-site-kit')}</h3>
                    <p className="description" style={{ marginBottom: '15px' }}>
                        {__('Select user roles that should not be tracked. This is useful to exclude administrators and editors from your analytics.', 'openmost-site-kit')}
                    </p>

                    {roles.length > 0 ? (
                        <div style={{
                            display: 'grid',
                            gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
                            gap: '10px',
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

    // Build URL for preview with debounce
    useEffect(() => {
        if (!settings?.host) return;

        const timer = setTimeout(() => {
            const url = new URL(`${settings.host}/index.php`);
            url.searchParams.set('module', 'CoreAdminHome');
            url.searchParams.set('action', 'optOutJS');
            url.searchParams.set('language', config.language);
            url.searchParams.set('showIntro', config.showIntro ? '1' : '0');
            // Only add style params if custom design is enabled
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
        // Only add style params if custom design is enabled
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
            <p className="description" style={{ marginBottom: '20px' }}>
                {__('Allow your visitors to opt-out of Matomo tracking to respect their privacy preferences.', 'openmost-site-kit')}
            </p>

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

                        <div style={{ marginTop: '16px' }}>
                            <ToggleControl
                                label={__('Show introduction text', 'openmost-site-kit')}
                                checked={config.showIntro}
                                onChange={(value) => setConfig({ ...config, showIntro: value })}
                                help={__('Display explanatory text before the opt-out checkbox', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </div>

                        <div style={{ marginTop: '16px' }}>
                            <ToggleControl
                                label={__('Custom design', 'openmost-site-kit')}
                                checked={config.customDesign}
                                onChange={(value) => setConfig({ ...config, customDesign: value })}
                                help={__('Customize colors and fonts of the opt-out form', 'openmost-site-kit')}
                                __nextHasNoMarginBottom
                            />
                        </div>

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
                    <h2>{__('Additional Information', 'openmost-site-kit')}</h2>
                </CardHeader>
                <CardBody>
                    <h3>{__('Available Shortcodes', 'openmost-site-kit')}</h3>
                    <ul style={{ marginLeft: '20px' }}>
                        <li>
                            <code>[matomo_opt_out]</code> - {__('Standard opt-out form', 'openmost-site-kit')}
                        </li>
                        <li>
                            <code>[omsk_matomo_opt_out]</code> - {__('Legacy shortcode (backward compatible)', 'openmost-site-kit')}
                        </li>
                    </ul>

                    <h3 style={{ marginTop: '20px' }}>{__('Shortcode Parameters', 'openmost-site-kit')}</h3>
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
                            <tr>
                                <td><code>font_size</code></td>
                                <td>{__('Font size', 'openmost-site-kit')}</td>
                                <td><code>14px</code></td>
                                <td><code>font_size="16px"</code></td>
                            </tr>
                            <tr>
                                <td><code>font_family</code></td>
                                <td>{__('Font family', 'openmost-site-kit')}</td>
                                <td><code>Arial</code></td>
                                <td><code>font_family="Helvetica"</code></td>
                            </tr>
                        </tbody>
                    </table>

                    <Notice status="info" isDismissible={false} style={{ marginTop: '20px' }}>
                        <p>
                            <strong>{__('GDPR Compliance:', 'openmost-site-kit')}</strong><br />
                            {__('Adding an opt-out form to your privacy policy page helps you comply with GDPR and other privacy regulations by giving users control over their data.', 'openmost-site-kit')}
                        </p>
                    </Notice>
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
        excludedRoles: [],
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
            setSettings(settingsData);
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

    const tabs = [
        {
            name: 'general',
            title: __('General', 'openmost-site-kit'),
            className: 'omsk-tab-general',
        },
        {
            name: 'privacy',
            title: __('Privacy', 'openmost-site-kit'),
            className: 'omsk-tab-privacy',
        },
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
                                roles={roles}
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
