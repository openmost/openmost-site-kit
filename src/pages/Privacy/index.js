/**
 * Privacy Page Component - Shortcode Builder
 */

import { useState, useEffect, useRef } from '@wordpress/element';
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
 * Uses a stable container ID and refs to avoid React DOM conflicts
 *
 * IMPORTANT: The Matomo script modifies DOM directly, so we must:
 * 1. Never render React children inside the container Matomo modifies
 * 2. Use a wrapper div for React state (loading spinner) separate from Matomo's target
 */
const OptOutPreview = ({ url, width, height }) => {
    const containerRef = useRef(null);
    const scriptRef = useRef(null);
    const [isLoading, setIsLoading] = useState(true);
    // Use a stable ID that doesn't change on every render
    const stableId = useRef(`matomo-opt-out-preview-${Date.now()}`);

    useEffect(() => {
        if (!url || !containerRef.current) return;

        const containerId = stableId.current;
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

        // Set the ID on the container
        containerRef.current.id = containerId;

        // Parse URL and update the divId parameter
        const scriptUrl = new URL(url);
        scriptUrl.searchParams.set('divId', containerId);

        // Create and load script
        const script = document.createElement('script');
        script.src = scriptUrl.toString();
        script.async = true;
        script.onload = () => setIsLoading(false);
        script.onerror = () => setIsLoading(false);
        scriptRef.current = script;

        document.body.appendChild(script);

        // Cleanup on unmount
        return () => {
            if (scriptRef.current && scriptRef.current.parentNode) {
                scriptRef.current.parentNode.removeChild(scriptRef.current);
                scriptRef.current = null;
            }
        };
    }, [url]);

    // Wrapper div for React to control, with Matomo container as empty ref
    // The Spinner is a SIBLING to the Matomo container, not a child
    return (
        <div style={{ position: 'relative', width: width === '100%' ? '100%' : width, minHeight: height }}>
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
            <div ref={containerRef} />
        </div>
    );
};

const Privacy = () => {
    const [loading, setLoading] = useState(true);
    const [settings, setSettings] = useState(null);
    const [copied, setCopied] = useState(false);
    // Debounced URL for preview - only updates after user stops changing config
    const [debouncedUrl, setDebouncedUrl] = useState('');

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

    // Debounce URL updates to avoid rapid script reloads
    useEffect(() => {
        if (!settings?.host) return;

        const timer = setTimeout(() => {
            const url = new URL(`${settings.host}/index.php`);
            url.searchParams.set('module', 'CoreAdminHome');
            url.searchParams.set('action', 'optOutJS');
            url.searchParams.set('language', config.language);
            url.searchParams.set('showIntro', config.showIntro ? '1' : '0');
            setDebouncedUrl(url.toString());
        }, 500);

        return () => clearTimeout(timer);
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

    if (loading) {
        return (
            <div style={{ padding: '20px', textAlign: 'center' }}>
                <Spinner />
            </div>
        );
    }

    const isConfigured = settings?.host;
    const shortcode = buildShortcode();

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
                                        url={debouncedUrl}
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
