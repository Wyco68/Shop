import './bootstrap';
import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.store('notifications', {
        notifications: [],
        unreadCount: 0,
        nextPageUrl: null,
        loading: false,
        _isListening: false,

        get items() {
            return this.notifications;
        },
        set items(val) {
            this.notifications = val;
        },

        init() {
            const store = Alpine.store('notifications');
            store.loadFromStorage();
            store.listenForNewNotifications();

            const userIdMeta = document.querySelector('meta[name="user-id"]');
            if (userIdMeta && store.notifications.length === 0) {
                store.fetchNotifications();
            }
        },

        loadFromStorage() {
            const userIdMeta = document.querySelector('meta[name="user-id"]');
            if (!userIdMeta) {
                this.clear();
                return;
            }
            const currentUserId = userIdMeta.content;
            try {
                const data = sessionStorage.getItem('carpart_notifications');
                if (data) {
                    const parsed = JSON.parse(data);
                    if (parsed.userId === currentUserId) {
                        this.notifications = parsed.notifications || [];
                        this.unreadCount = parsed.unreadCount || 0;
                        this.nextPageUrl = parsed.nextPageUrl || null;
                        return;
                    }
                }
            } catch (e) {
                // ignore storage errors
            }
            this.clear();
        },

        saveToStorage() {
            const userIdMeta = document.querySelector('meta[name="user-id"]');
            if (!userIdMeta) return;
            const currentUserId = userIdMeta.content;
            try {
                sessionStorage.setItem('carpart_notifications', JSON.stringify({
                    userId: currentUserId,
                    notifications: this.notifications,
                    unreadCount: this.unreadCount,
                    nextPageUrl: this.nextPageUrl
                }));
            } catch (e) {
                // ignore storage errors
            }
        },

        clear() {
            this.notifications = [];
            this.unreadCount = 0;
            this.nextPageUrl = null;
            try {
                sessionStorage.removeItem('carpart_notifications');
            } catch (e) {}
        },

        async fetchNotifications(url = '/notifications') {
            const store = Alpine.store('notifications');
            if (store.loading || (url !== '/notifications' && !store.nextPageUrl)) return;
            store.loading = true;
            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) {
                    if (response.status === 401) return;
                    throw new Error('Network response was not ok');
                }
                const data = await response.json();

                if (url === '/notifications') {
                    store.notifications = data.notifications.data;
                } else {
                    store.notifications = [...store.notifications, ...data.notifications.data];
                }

                store.unreadCount = data.unreadCount;
                store.nextPageUrl = data.notifications.next_page_url;
                store.saveToStorage();
            } catch (error) {
                // silently fail
            } finally {
                store.loading = false;
            }
        },

        setNotifications(notificationsArray) {
            const store = Alpine.store('notifications');
            store.notifications = notificationsArray;
            store.saveToStorage();
        },

        addNotification(payload) {
            const store = Alpine.store('notifications');
            const newNotification = {
                id: payload.id,
                type: payload.type,
                title: payload.title,
                message: payload.message,
                read_at: null,
                created_at: payload.created_at || new Date().toISOString(),
                data: payload.data || null
            };
            store.notifications = [newNotification, ...store.notifications];
            store.unreadCount++;
            store.saveToStorage();
        },

        markAsRead(id) {
            const store = Alpine.store('notifications');
            const index = store.notifications.findIndex(n => n.id === id);
            if (index !== -1 && !store.notifications[index].read_at) {
                store.notifications[index].read_at = new Date().toISOString();
                store.unreadCount = Math.max(0, store.unreadCount - 1);
                store.saveToStorage();

                fetch(`/notifications/${id}/read`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(() => {});
            }
        },

        markAllAsRead() {
            const store = Alpine.store('notifications');
            let hasUnread = false;
            store.notifications.forEach(n => {
                if (!n.read_at) {
                    n.read_at = new Date().toISOString();
                    hasUnread = true;
                }
            });
            if (hasUnread) {
                store.unreadCount = 0;
                store.saveToStorage();

                fetch(`/notifications/read-all`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(() => {});
            }
        },

        listenForNewNotifications() {
            const store = Alpine.store('notifications');
            if (store._isListening) return;

            const userIdMeta = document.querySelector('meta[name="user-id"]');
            if (userIdMeta && window.Echo) {
                store._isListening = true;
                const userId = userIdMeta.content;
                const channelName = `private-user.${userId}`;

                try {
                    const channel = window.Echo.private(channelName);

                    channel.listen('.NotificationCreated', (e) => {
                        Alpine.store('notifications').addNotification(e);
                    });
                } catch (err) {
                    // silently fail
                }
            }
        }
    });
});

window.Alpine = Alpine;
Alpine.start();
