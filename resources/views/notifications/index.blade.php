@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Notifications</h1>
            
            <template x-if="$store.notifications.unreadCount > 0">
                <button @click="markAllAsRead" class="text-sm font-medium text-sky-600 hover:text-sky-700 transition-colors bg-sky-50 px-4 py-2 rounded-lg hover:bg-sky-100">
                    Mark all as read
                </button>
            </template>
        </div>

        <div class="p-0">
            <template x-if="$store.notifications.notifications.length === 0 && !$store.notifications.loading">
                <div class="text-center py-16 px-4">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 mb-1">No notifications yet</h3>
                    <p class="text-slate-500 text-sm">When you get updates about your orders, they'll show up here.</p>
                </div>
            </template>

            <div class="divide-y divide-slate-100" @scroll.window="onScroll">
                <template x-for="notification in $store.notifications.notifications" :key="notification.id">
                    <div @click="markAsReadAndRedirect(notification)" 
                         class="p-6 transition-colors cursor-pointer flex items-start gap-4 hover:bg-slate-50"
                         :class="notification.read_at ? 'opacity-70' : 'bg-sky-50/30'">
                        
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                             :class="notification.read_at ? 'bg-slate-100 text-slate-400' : 'bg-sky-100 text-sky-600'">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-4 mb-1">
                                <p class="text-sm font-semibold text-slate-900 truncate" x-text="notification.title"></p>
                                <span class="text-xs text-slate-400 whitespace-nowrap" x-text="formatDate(notification.created_at)"></span>
                            </div>
                            <p class="text-sm text-slate-600 leading-relaxed" x-text="notification.message"></p>
                        </div>
                        
                        <div class="shrink-0 w-3 h-3 mt-1.5" x-show="!notification.read_at">
                            <div class="w-2.5 h-2.5 rounded-full bg-sky-500"></div>
                        </div>
                    </div>
                </template>
            </div>

            <template x-if="$store.notifications.loading">
                <div class="py-8 flex justify-center">
                    <svg class="animate-spin h-6 w-6 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Expose page-specific scroll logic & helper methods directly on window
    // This avoids race conditions if alpine:init has already fired
    window.onScroll = () => {
        const bottomOfWindow = Math.max(window.pageYOffset, document.documentElement.scrollTop, document.body.scrollTop) + window.innerHeight === document.documentElement.offsetHeight;
        
        if (bottomOfWindow && Alpine.store('notifications').nextPageUrl && !Alpine.store('notifications').loading) {
            Alpine.store('notifications').fetchNotifications(Alpine.store('notifications').nextPageUrl);
        }
    };

    window.markAsReadAndRedirect = (notification) => {
        if (!notification.read_at) {
            Alpine.store('notifications').markAsRead(notification.id);
        }
        
        if (notification.data && notification.data.route) {
            window.location.href = notification.data.route.replace("{id}", notification.data.order_id);
        } else if (notification.data && notification.data.order_id) {
            window.location.href = `/orders/${notification.data.order_id}`;
        }
    };

    window.markAllAsRead = () => {
        Alpine.store('notifications').markAllAsRead();
    };

    window.formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };
</script>
@endpush
