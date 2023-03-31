import {createApp} from 'vue'
import Dashboard from './pages/Dashboard.vue';

const mountEl = document.querySelector("#osk-dashboard");

createApp(Dashboard, {...mountEl.dataset}).mount('#osk-dashboard');
