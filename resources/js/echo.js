import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: window.PUSHER_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: window.PUSHER_APP_CLUSTER || import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});
