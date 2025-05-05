<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-gradient-to-b from-blue-700 to-blue-900 text-white flex flex-col">
            <div class="p-6">
                <h1 class="text-3xl font-bold text-center">Admin</h1>
            </div>

            <nav class="flex-1 px-4 space-y-2">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                    <!-- Home Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-none" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7m-9 9v-5h4v5m5-4l2 2m-2-2v7H5v-7" />
                    </svg>
                    <span class="ml-3 font-medium">Dashboard</span>
                </a>

                <a href="{{ route('upload.form') }}"
                    class="flex items-center px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                    <!-- Upload Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-none" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v8m0-8l-3 3m3-3l3 3M12 4v8" />
                    </svg>
                    <span class="ml-3 font-medium">Upload CSV</span>
                </a>
            </nav>

            <div class="mt-auto p-4">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-2 rounded-lg transition">
                        <!-- Logout Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-none" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                        </svg>
                        <span class="ml-3 font-medium">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto p-6">
            <header class="mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
            </header>

            <main class="bg-white shadow-xl rounded-lg p-6 transform transition duration-300">
                @yield('content')
                @stack('scripts')
            </main>
        </div>
    </div>
</body>

</html>
