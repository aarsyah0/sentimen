<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Aplikasi Analisa Sentiment</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- AOS CSS -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <style>
        body {
            background: #f0f8ff;
            font-family: 'Roboto', sans-serif;
            overflow-x: hidden;
        }

        .sidebar {
            width: 260px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: -260px;
            background: #ffffff;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease;
            z-index: 1035;
            display: flex;
            flex-direction: column;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
        }

        .sidebar-header img {
            max-height: 64px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .sidebar-header img:hover {
            transform: scale(1.05);
        }

        .nav-sidebar {
            flex-grow: 1;
            padding-top: 1rem;
        }

        .nav-sidebar .nav-link {
            color: #495057;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            border-radius: 0.375rem;
            margin: 0.25rem 0.5rem;
            transition: background 0.2s, color 0.2s;
            font-weight: 500;
        }

        .nav-sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 0.75rem;
        }

        .nav-sidebar .nav-link:hover {
            background: #f1f3f5;
            color: #212529;
        }

        .nav-sidebar .nav-link.active {
            background: #e9ecef;
            color: #0d6efd;
            border-left: 4px solid #0d6efd;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid #e9ecef;
        }

        /* Content wrapper */
        .content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
            padding-top: 56px;
        }

        .content.shift {
            margin-left: 260px;
        }

        /* Navbar */
        .navbar-brand {
            font-weight: 600;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container-fluid">
            <button class="btn btn-primary" id="btnToggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="container-fluid justify-content-center align-items-center d-flex">

                <img src="{{ asset('assets/pj.png') }}" alt="Logo" class="img-fluid" style="max-height: 50px;">
                <span class="navbar-brand ms-2 mb-0">Aplikasi Analisa Sentiment</span>
            </div>
        </div>
    </nav>
    <!-- Sidebar -->
    <div class="sidebar bg-white" id="sidebar">
        <!-- Logo and Toggle -->
        <div class="sidebar-header" id="logoToggle">
            <img src="{{ asset('assets/pj.png') }}" alt="Logo">
        </div>

        <!-- Navigation Links -->

        <nav class="nav flex-column nav-sidebar" data-aos="fade-right">
            <a href="{{ route('sentiment.dashboard') }}"
                class="nav-link {{ request()->routeIs('sentiment.dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="{{ route('scrap.tweets.form') }}"
                class="nav-link {{ request()->routeIs('scrap.tweets.form') ? 'active' : '' }}">
                <i class="fas fa-search"></i>
                Scraping Tweet
            </a>
            <a href="{{ route('upload.form') }}"
                class="nav-link {{ request()->routeIs('upload.form') ? 'active' : '' }}">
                <i class="fas fa-cogs"></i>
                Preprocessing Data
            </a>
            <a href="{{ route('data.result') }}"
                class="nav-link {{ request()->routeIs('data.result') ? 'active' : '' }}">
                <i class="fas fa-table"></i>
                Preprocessing Hasil
            </a>
            <a href="{{ route('sentiment.upload') }}"
                class="nav-link {{ request()->routeIs('sentiment.upload') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                Klasifikasi
            </a>
            <a href="{{ route('sentiment.report') }}"
                class="nav-link {{ request()->routeIs('sentiment.report', '/') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i>
                Visualisasi
            </a>

        </nav>

        <!-- Optional Footer -->
        <div class="sidebar-footer text-center small text-muted">
            &copy; 2025 SentimentApp
        </div>
    </div>


    <!-- Main content -->
    <div class="content" id="content">
        <main class="container-fluid p-4" data-aos="fade-up">
            <div class="mb-4 border-bottom align-content-center text-center">
                <h2 class="fw-normal">Analisis Sentimen Menggunakan Naive Bayes</h2>
                <p class="text-lg text-gray-600">Tugas Akhir Oktaviarlen Setya (E31221299)</p>
            </div>

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="alert alert-success" data-aos="fade-in">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger" data-aos="shake">{{ session('error') }}</div>
            @endif
            <!-- Page content -->
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });
        const btn = document.getElementById('btnToggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const logo = document.getElementById('logoToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            content.classList.toggle('shift');
        }

        btn.addEventListener('click', toggleSidebar);
        logo.addEventListener('click', toggleSidebar);
    </script>
    <script src="https://cdn.tailwindcss.com"></script>

    @stack('scripts')
</body>

</html>
