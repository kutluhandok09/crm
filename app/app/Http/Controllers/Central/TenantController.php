<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Tenant::class);

        return view('central.tenants.index', [
            'tenants' => Tenant::query()
                ->visibleTo($request->user())
                ->with(['domains', 'reseller'])
                ->latest('created_at')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Tenant::class);

        return view('central.tenants.create', [
            'resellers' => User::query()
                ->role('reseller')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Tenant::class);

        $centralDomains = collect(config('tenancy.central_domains'))
            ->map(fn (string $domain): string => strtolower($domain))
            ->all();

        $validated = $request->validate([
            'tenant_id' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/', 'unique:tenants,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain', Rule::notIn($centralDomains)],
            'default_currency' => ['required', Rule::in(['TRY', 'GBP', 'EUR', 'USD'])],
            'reseller_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $creator = $request->user();
        $resellerId = $creator->isReseller()
            ? $creator->getKey()
            : ($validated['reseller_id'] ?? null);

        $payload = [
            'reseller_id' => $resellerId,
            'data' => [
                'company_name' => $validated['company_name'],
                'default_currency' => $validated['default_currency'],
            ],
        ];

        if (! empty($validated['tenant_id'])) {
            $payload['id'] = Str::lower($validated['tenant_id']);
        }

        /** @var \App\Models\Tenant $tenant */
        $tenant = Tenant::create($payload);
        $tenant->createDomain(Str::lower($validated['domain']));

        return redirect()
            ->route('central.tenants.show', $tenant)
            ->with('status', 'tenant-created');
    }

    public function show(Request $request, Tenant $tenant): View
    {
        $this->authorize('view', $tenant);

        $tenant->load(['domains', 'reseller', 'users']);

        return view('central.tenants.show', [
            'tenant' => $tenant,
        ]);
    }
}
