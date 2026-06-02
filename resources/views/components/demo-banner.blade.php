@if(config('app.demo_mode'))
    <div class="bg-amber-50 border-b border-amber-200 text-amber-950 text-sm" role="status">
        <div class="max-w-7xl mx-auto px-4 py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <p class="font-medium">
                Portfolio demo — data resets on redeploy. Payment proofs may not persist on free hosting.
            </p>
            <p class="text-amber-900/80 text-xs sm:text-sm shrink-0">
                Admin: <code class="bg-amber-100/80 px-1 rounded">admin@carpart.test</code>
                · Customer: <code class="bg-amber-100/80 px-1 rounded">user@carpart.test</code>
                · Password: <code class="bg-amber-100/80 px-1 rounded">password</code>
            </p>
        </div>
    </div>
@endif
