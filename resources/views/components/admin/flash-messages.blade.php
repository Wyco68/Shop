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
    @if(session('warning'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-y-2 opacity-0"
             x-transition:enter-end="transform translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-y-0 opacity-100"
             x-transition:leave-end="transform translate-y-2 opacity-0"
             class="bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3 rounded-xl shadow-lg shadow-amber-100/30 text-sm flex items-center gap-3">
            <div class="w-6 h-6 rounded-lg bg-amber-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.598c.75 1.334-.213 2.987-1.743 2.987H3.482c-1.53 0-2.493-1.653-1.743-2.987L8.257 3.1zM11 14a1 1 0 11-2 0 1 1 0 012 0zm-.25-6.75a.75.75 0 00-1.5 0v3.5a.75.75 0 001.5 0v-3.5z" clip-rule="evenodd"/></svg>
            </div>
            <div>
                <p class="font-semibold text-amber-950">Warning</p>
                <p class="text-amber-700/90 text-xs mt-0.5">{{ session('warning') }}</p>
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
    @if(session('fail'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-y-2 opacity-0"
             x-transition:enter-end="transform translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-y-0 opacity-100"
             x-transition:leave-end="transform translate-y-2 opacity-0"
             class="bg-slate-900 border border-slate-700 text-slate-100 px-5 py-3 rounded-xl shadow-lg shadow-slate-900/30 text-sm flex items-center gap-3">
            <div class="w-6 h-6 rounded-lg bg-rose-600 flex items-center justify-center text-white shrink-0 shadow-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </div>
            <div>
                <p class="font-semibold text-white">Failed</p>
                <p class="text-slate-300 text-xs mt-0.5">{{ session('fail') }}</p>
            </div>
        </div>
    @endif
</div>
