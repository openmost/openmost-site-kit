/**
 * Privacy Page Component - Shortcode Builder
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Card,
    CardBody,
    CardHeader,
    SelectControl,
    TextControl,
    ToggleControl,
    Button,
    Notice,
    Spinner,
    Flex,
    FlexBlock,
    FlexItem,
    __experimentalDivider as Divider,
} from '@wordpress/components';
import { copy, check } from '@wordpress/icons';
import { getSettings } from '../../utils/api';

/**
 * OptOut Preview Component
 * Dynamically loads Matomo opt-out script
 */
const OptOutPreview = ({ url, width, height, previewId }) => {
    const containerId = `matomo-opt-out-preview-${previewId}`;

    useEffect(() => {
        if (!url) return;

        // Clean up existing Matomo opt-out scripts
        const existingScripts = document.querySelectorAll('script[src*="optOutJS"]');
        existingScripts.forEach(script => {
            if (script.parentNode) {
                script.parentNode.removeChild(script);
            }
        });

        // Wait a bit for cleanup
        const timer = setTimeout(() => {
            // Create new script element
            const script = document.createElement('script');
            script.src = url;
            script.async = true;

            // Append script to document
            document.body.appendChild(script);
        }, 100);

        // Cleanup function
        return () => {
            clearTimeout(timer);
        };
    }, [url]);

    return (
        <div
            id={containerId}
            style={{
                width: width === '100%' ? '100%' : width,
                minHeight: height,
            }}
        />
    );
};

const Privacy = () => {
    const [loading, setLoading] = useState(true);
    const [settings, setSettings] = useState(null);
    const [copied, setCopied] = useState(false);
    const [previewKey, setPreviewKey] = useState(0);

    // Shortcode configuration
    const [config, setConfig] = useState({
        language: 'auto',
        showIntro: true,
        width: '100%',
        height: '200px',
    });

    useEffect(() => {
        loadSettings();
    }, []);

    // Reload preview when config changes
    useEffect(() => {
        if (settings?.host) {
            // Force re-render of preview by changing key
            setPreviewKey(prev => prev + 1);
        }
    }, [config, settings]);

    const loadSettings = async () => {
        try {
            const data = await getSettings();
            setSettings(data);
        } catch (error) {
            console.error('Failed to load settings', error);
        } finally {
            setLoading(false);
        }
    };

    const buildShortcode = () => {
        const parts = ['[matomo_opt_out'];

        if (config.language !== 'auto') {
            parts.push(` language="${config.language}"`);
        }

        if (!config.showIntro) {
            parts.push(' show_intro="0"');
        }

        if (config.width !== '100%') {
            parts.push(` width="${config.width}"`);
        }

        if (config.height !== '200px') {
            parts.push(` height="${config.height}"`);
        }

        parts.push(']');
        return parts.join('');
    };

    const handleCopy = async () => {
        const shortcode = buildShortcode();
        try {
            await navigator.clipboard.writeText(shortcode);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
    };

    const buildOptOutUrl = () => {
        if (!settings?.host) return '';

        const url = new URL(`${settings.host}/index.php`);
        url.searchParams.set('module', 'CoreAdminHome');
        url.searchParams.set('action', 'optOutJS');
        url.searchParams.set('divId', `matomo-opt-out-preview-${previewKey}`);
        url.searchParams.set('language', config.language);
        url.searchParams.set('showIntro', config.showIntro ? '1' : '0');

        return url.toString();
    };

    if (loading) {
        return (
            <div style={{ padding: '20px', textAlign: 'center' }}>
                <Spinner />
            </div>
        );
    }

    const isConfigured = settings?.host;
    const shortcode = buildShortcode();
    const optOutUrl = buildOptOutUrl();

    return (
        <div className="omsk-privacy">
            <h1>{__('Privacy & Opt-Out', 'openmost-site-kit')}</h1>

            <p className="description">
                {__('Allow your visitors to opt-out of Matomo tracking to respect their privacy preferences.', 'openmost-site-kit')}
            </p>

            {!isConfigured && (
                <Notice status="warning" isDismissible={false}>
                    {__('Matomo is not configured. Please configure your Matomo instance in the settings first.', 'openmost-site-kit')}
                </Notice>
            )}

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginTop: '20px' }}>
                {/* Shortcode Builder */}
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
                        />

                        <ToggleControl
                            label={__('Show introduction text', 'openmost-site-kit')}
                            checked={config.showIntro}
                            onChange={(value) => setConfig({ ...config, showIntro: value })}
                            help={__('Display explanatory text before the opt-out checkbox', 'openmost-site-kit')}
                        />

                        <Divider />

                        <h3 style={{ marginTop: '20px', marginBottom: '10px' }}>
                            {__('Display Options', 'openmost-site-kit')}
                        </h3>

                        <TextControl
                            label={__('Width', 'openmost-site-kit')}
                            value={config.width}
                            onChange={(value) => setConfig({ ...config, width: value })}
                            help={__('Width of the opt-out form (e.g., 100%, 600px)', 'openmost-site-kit')}
                        />

                        <TextControl
                            label={__('Height', 'openmost-site-kit')}
                            value={config.height}
                            onChange={(value) => setConfig({ ...config, height: value })}
                            help={__('Minimum height of the opt-out form (e.g., 200px)', 'openmost-site-kit')}
                        />

                        <Divider />

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

                {/* Live Preview */}
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
                                    backgroundColor: 'white',
                                    minHeight: config.height,
                                }}>
                                    <OptOutPreview
                                        key={previewKey}
                                        previewId={previewKey}
                                        url={optOutUrl}
                                        width={config.width}
                                        height={config.height}
                                    />
                                </div>

                                <Notice status="success" isDismissible={false} style={{ marginTop: '15px' }}>
                                    {__('The preview above is fully functional. You can test the opt-out functionality here.', 'openmost-site-kit')}
                                </Notice>
                            </>
                        ) : (
                            <Notice status="warning" isDismissible={false}>
                                {__('Configure Matomo in the settings to see a live preview.', 'openmost-site-kit')}
                            </Notice>
                        )}
                    </CardBody>
                </Card>
            </div>

            {/* Additional Documentation */}
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
                                <td><code>width</code></td>
                                <td>{__('Form width', 'openmost-site-kit')}</td>
                                <td><code>100%</code></td>
                                <td><code>width="600px"</code></td>
                            </tr>
                            <tr>
                                <td><code>height</code></td>
                                <td>{__('Minimum form height', 'openmost-site-kit')}</td>
                                <td><code>200px</code></td>
                                <td><code>height="250px"</code></td>
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
        </div>
    );
};

export default Privacy;
