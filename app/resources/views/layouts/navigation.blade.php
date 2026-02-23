<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ request()->routeIs('tenant.*') ? route('tenant.dashboard') : url('/dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if (request()->routeIs('tenant.*'))
                        <x-nav-link :href="route('tenant.dashboard')" :active="request()->routeIs('tenant.dashboard')">
                            Tenant Panel
                        </x-nav-link>
                        <x-nav-link :href="route('tenant.products.index')" :active="request()->routeIs('tenant.products.*')">
                            Urunler
                        </x-nav-link>
                        <x-nav-link :href="route('tenant.invoices.index')" :active="request()->routeIs('tenant.invoices.*')">
                            Faturalar
                        </x-nav-link>
                    @else
                        <x-nav-link :href="url('/dashboard')" :active="request()->routeIs('dashboard')">
                            Merkezi Dashboard
                        </x-nav-link>
                        <x-nav-link :href="route('central.tenants.index')" :active="request()->routeIs('central.tenants.*')">
                            Firmalar
                        </x-nav-link>
                        @if (auth()->user()->isSuperAdmin())
                            <x-nav-link :href="route('central.resellers.index')" :active="request()->routeIs('central.resellers.*')">
                                Bayiler
                            </x-nav-link>
                        @endif
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="60">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-2 text-xs rounded-full bg-gray-100 px-2 py-1">
                                {{ Auth::user()->getRoleNames()->implode(', ') ?: 'user' }}
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Profil
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Cikis
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if (request()->routeIs('tenant.*'))
                <x-responsive-nav-link :href="route('tenant.dashboard')" :active="request()->routeIs('tenant.dashboard')">
                    Tenant Panel
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('tenant.products.index')" :active="request()->routeIs('tenant.products.*')">
                    Urunler
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('tenant.invoices.index')" :active="request()->routeIs('tenant.invoices.*')">
                    Faturalar
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="url('/dashboard')" :active="request()->routeIs('dashboard')">
                    Merkezi Dashboard
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('central.tenants.index')" :active="request()->routeIs('central.tenants.*')">
                    Firmalar
                </x-responsive-nav-link>
                @if (auth()->user()->isSuperAdmin())
                    <x-responsive-nav-link :href="route('central.resellers.index')" :active="request()->routeIs('central.resellers.*')">
                        Bayiler
                    </x-responsive-nav-link>
                @endif
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Profil
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        Cikis
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
