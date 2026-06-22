{{-- Flash Messages (Alpine auto-dismiss) --}}
<div class="fixed top-20 right-6 z-50 space-y-3">
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-y-2 opacity-0"
             x-transition:enter-end="transform translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-y-0 opacity-100"
             x-transition:leave-end="transform translate-y-2 opacity-0"
             class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3 rounded-xl shadow-lg shadow-emerald-100/30 text-sm flex items-center gap-3">
            <div class="w-6 h-6 rounded-lg bg-emerald-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            </div>
            <div>
                <p class="font-semibold text-emerald-950">Success</p>
                <p class="text-emerald-700/90 text-xs mt-0.5">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-y-2 opacity-0"
             x-transition:enter-end="transform translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-y-0 opacity-100"
             x-transition:leave-end="transform translate-y-2 opacity-0"
             class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-3 rounded-xl shadow-lg shadow-rose-100/30 text-sm flex items-center gap-3">
            <div class="w-6 h-6 rounded-lg bg-rose-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 012 0v4a1 1 0 01-2 0V9zm0 6a1 1 0 112 0 1 1 0 01-2 0z" clip-rule="evenodd"/></svg>
            </div>
            <div>
                <p class="font-semibold text-rose-950">Error</p>
                <p class="text-rose-700/90 text-xs mt-0.5">{{ session('error') }}</p>
            </div>
        </div>
    @endif
</div>
