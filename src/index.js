/**
 * Main entry point for Openmost Site Kit
 * This file registers Settings, Dashboard, and Post Analytics React apps
 */

import { render } from '@wordpress/element';
import Settings from './pages/Settings';
import Dashboard from './pages/Dashboard';
import PostAnalytics from './components/PostAnalytics';
import './scss/app.scss';

// Initialize Settings page
const settingsRoot = document.getElementById('omsk-settings-root');
if (settingsRoot) {
    render(<Settings />, settingsRoot);
}

// Initialize Dashboard page
const dashboardRoot = document.getElementById('omsk-dashboard-root');
if (dashboardRoot) {
    render(<Dashboard />, dashboardRoot);
}

// Initialize Post Analytics metaboxes (can be multiple on the page)
document.querySelectorAll('[id^="omsk-post-analytics-root-"]').forEach(root => {
    const postId = root.getAttribute('data-post-id');
    if (postId) {
        render(<PostAnalytics postId={parseInt(postId)} />, root);
    }
});
