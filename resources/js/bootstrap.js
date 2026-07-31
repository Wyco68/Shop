import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (reverbKey || pusherKey) {
    const { default: Echo } = await import('laravel-echo');
    const { default: Pusher } = await import('pusher-js');

    window.Pusher = Pusher;

    if (reverbKey) {
        const port = import.meta.env.VITE_REVERB_PORT ?? 443;
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: port,
            wssPort: port,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } else {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
        });
    }
}
