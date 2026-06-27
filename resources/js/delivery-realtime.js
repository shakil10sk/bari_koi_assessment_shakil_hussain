/**
 * Part 7.2 — Frontend real-time subscription for driver delivery status updates.
 *
 * Stack: Laravel Echo + Pusher JS client
 * Channel: private (requires channel auth from /broadcasting/auth)
 *
 * Usage:
 *   import { subscribeDriverUpdates, unsubscribeDriverUpdates } from './delivery-realtime';
 *   subscribeDriverUpdates(driverId, (event) => console.log(event));
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Initialise Echo once at app bootstrap
const echo = new Echo({
    broadcaster:    'pusher',
    key:            import.meta.env.VITE_PUSHER_APP_KEY,
    cluster:        import.meta.env.VITE_PUSHER_APP_CLUSTER,
    wsHost:         import.meta.env.VITE_PUSHER_HOST ?? window.location.hostname,
    wsPort:         import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort:        import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS:       (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Echo sends this header for the /broadcasting/auth POST to identify the user
    authEndpoint:   '/broadcasting/auth',
});

/**
 * Subscribe a driver to their private delivery-status channel.
 *
 * @param {number}   driverId  - The authenticated driver's user ID
 * @param {Function} onUpdate  - Callback invoked with the broadcast payload
 * @returns {Object} Echo channel instance (call .stopListening() to unsubscribe)
 */
export function subscribeDriverUpdates(driverId, onUpdate) {
    return echo
        .private(`driver.${driverId}`)
        // Event name must match broadcastAs() in DeliveryStatusChanged
        .listen('.delivery.status.changed', (payload) => {
            onUpdate({
                deliveryId:      payload.delivery_id,
                trackingNumber:  payload.tracking_number,
                previousStatus:  payload.previous_status,
                newStatus:       payload.new_status,
                updatedAt:       new Date(payload.updated_at),
            });
        })
        .error((error) => {
            console.error('[Echo] Channel auth failed:', error);
        });
}

/**
 * Subscribe a user to their import-progress channel.
 *
 * @param {number}   userId    - The authenticated user's ID
 * @param {Function} onStarted - Callback when import.started fires
 */
export function subscribeImportProgress(userId, onStarted) {
    return echo
        .private(`user.${userId}`)
        .listen('.import.started', (payload) => {
            onStarted({
                importJobId: payload.import_job_id,
                filename:    payload.filename,
                status:      payload.status,
            });
        });
}

export { echo };
