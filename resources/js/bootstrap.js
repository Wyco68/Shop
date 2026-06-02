import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    const { default: Echo } = await import('laravel-echo');
    const { default: Pusher } = await import('pusher-js');

    window.Pusher = Pusher;

    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
    const useTls = scheme === 'https';
    const port = Number(import.meta.env.VITE_REVERB_PORT ?? (useTls ? 443 : 8080));

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: port,
        wssPort: port,
        forceTLS: useTls,
        enabledTransports: useTls ? ['wss', 'ws'] : ['ws'],
    });
}
