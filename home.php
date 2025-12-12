<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCTV Monitoring System - Kabupaten Rembang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .logo i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.3rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.8rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-outline {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            padding: 100px 5% 50px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.5;
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-text p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-white {
            background: white;
            color: #667eea;
            font-weight: 700;
        }

        .btn-white:hover {
            background: #f8f9fa;
        }

        .hero-image {
            position: relative;
        }

        .hero-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Stats Section */
        .stats {
            padding: 80px 5%;
            background: white;
        }

        .stats-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            border-radius: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .stat-card p {
            color: #64748b;
            font-size: 1.1rem;
        }

        /* Features Section */
        .features {
            padding: 80px 5%;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: #1e293b;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .section-title p {
            font-size: 1.1rem;
            color: #64748b;
        }

        .features-grid {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .feature-card p {
            color: #64748b;
            line-height: 1.8;
        }

        /* CTA Section */
        .cta {
            padding: 100px 5%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat center;
            background-size: cover;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .cta h2 {
            font-size: 3rem;
            color: white;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .cta p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2.5rem;
        }

        /* Footer */
        .footer {
            padding: 2rem 40px;
            background: #1e293b;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 3rem;
            max-width: 1400px;
            margin: 0 auto 2rem;
        }

        .footer-section h3 {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-section h3 i {
            color: #667eea;
        }

        .footer-section p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
            font-size: 0.9rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .footer-links a:hover {
            color: #667eea;
            padding-left: 5px;
        }

        .footer-links a i {
            font-size: 0.8rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-links a:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.9rem;
        }

        .footer-bottom strong {
            color: #667eea;
        }

        @media (max-width: 1024px) {
            .footer {
                margin-left: 250px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .footer {
                margin-left: 0;
                padding: 2rem 20px;
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .nav-links {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .hero-text h1 {
                font-size: 2rem;
            }

            .hero-text p {
                font-size: 1rem;
            }

            .auth-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .cta h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-video"></i>
            <span>CCTV Rembang</span>
        </div>
        <div class="nav-links">
            <a href="#home">Beranda</a>
            <a href="#features">Fitur</a>
            <a href="#about">Tentang</a>
            <a href="#contact">Kontak</a>
        </div>
        <div class="auth-buttons">
            <!-- <a href="register.php" class="btn btn-outline">
                <i class="fas fa-user-plus"></i> Registrasi
            </a> -->
            <a href="maps.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Lihat CCTV
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Sistem Monitoring CCTV Kabupaten Rembang</h1>
                <p>Pantau keamanan dan lalu lintas kota secara real-time dengan teknologi monitoring CCTV modern. Akses mudah, cepat, dan aman kapan saja, dimana saja.</p>
                <div class="hero-buttons">
                    <a href="maps.php" class="btn btn-white">
                        <i class="fas fa-play-circle"></i> Lihat CCTV
                    </a>
                    <!-- <a href="register.php" class="btn btn-outline btn-white">
                        <i class="fas fa-user-plus"></i> Daftar Sekarang
                    </a> -->
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1557597774-9d273605dfa9?w=600&h=400&fit=crop" alt="CCTV Monitoring">
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-video"></i>
                </div>
                <h3>20+</h3>
                <p>CCTV Aktif</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3>20+</h3>
                <p>Lokasi Strategis</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>24/7</h3>
                <p>Monitoring Real-time</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>100%</h3>
                <p>Aman & Terpercaya</p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-title">
            <h2>Fitur Unggulan</h2>
            <p>Berbagai fitur canggih untuk memudahkan monitoring</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <h3>Live Streaming</h3>
                <p>Pantau kondisi real-time dari semua lokasi CCTV dengan kualitas video HD yang jernih dan lancar.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-map"></i>
                </div>
                <h3>Peta Interaktif</h3>
                <p>Lihat lokasi semua CCTV dalam peta interaktif yang mudah digunakan dan informatif.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Dashboard Analytics</h3>
                <p>Analisis data dan statistik lengkap dengan visualisasi yang mudah dipahami.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-car"></i>
                </div>
                <h3>Deteksi Kendaraan</h3>
                <p>Sistem otomatis mendeteksi kendaraan yang melintas.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Responsive Design</h3>
                <p>Akses dari berbagai perangkat - desktop, tablet, atau smartphone dengan tampilan optimal.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3>Keamanan Terjamin</h3>
                <p>Sistem keamanan berlapis dengan enkripsi data untuk melindungi privasi pengguna.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta" id="about">
        <div class="cta-content">
            <h2>Siap Memulai Monitoring?</h2>
            <p>Bergabunglah dengan sistem monitoring CCTV modern Kabupaten Rembang. Daftar sekarang dan dapatkan akses ke beberapa fitur!</p>
            <div class="hero-buttons">
                <!-- <a href="register.php" class="btn btn-white">
                    <i class="fas fa-user-plus"></i> Daftar Gratis
                </a> -->
                <a href="maps.php" class="btn btn-outline" style="color: white; border-color: white;">
                    <i class="fas fa-sign-in-alt"></i> Lihat CCTV
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
<footer class="footer" id="contact">
    <div class="footer-content">
        <div class="footer-section">
            <!-- Logo Footer -->
            <!-- <img src="uploads/logo.png" alt="Logo CCTV" class="footer-logo" onerror="this.style.display='none'"> -->
            <h3>
                <i class="fas fa-video"></i>
                CCTV Monitoring System
            </h3>
            <p>
                Sistem monitoring CCTV modern untuk Kabupaten Rembang. 
                Memantau keamanan dan lalu lintas kota secara real-time dengan teknologi terkini.
            </p>
            <div class="social-links">
                <a href="https://www.facebook.com/ghost" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.tiktok.com/@rembangkab" title="TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="https://www.instagram.com/rembangkab" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://maps.app.goo.gl/EC8tH7vLzesceoes9" title="Lokasi"><i class="fas fa-map-marker-alt"></i></a>
            </div>
        </div>

        <div class="footer-section">
            <h3>
                <i class="fas fa-clock"></i>
                Waktu Pelayanan
            </h3>
            <div class="schedule-item">
                <p><span class="schedule-day">Senin - Kamis</span>
                <span class="schedule-time">07:30 - 16:00</span></p>
            </div>
            <div class="schedule-item">
                <p><span class="schedule-day">Jumat</span>
                <span class="schedule-time">07:30 - 11:00</span></p>
            </div>
            <div class="schedule-item">
                <p><span class="schedule-day">Sabtu - Minggu</span>
                <span class="schedule-closed">LIBUR</span></p>
            </div>
        </div>

        <div class="footer-section">
            <h3>
                <i class="fas fa-info-circle"></i>
                Informasi
            </h3>
            <ul class="footer-links">
                <li><a href="https://dinkominfo.rembangkab.go.id"><i class="fas fa-chevron-right"></i> Tentang Kami</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>
            &copy; <?= date('Y') ?> <strong>CCTV Monitoring System</strong> - Kabupaten Rembang. 
            All Rights Reserved. Made with <i class="fas fa-heart" style="color: #ef4444;"></i> by Tim Pengembang
        </p>
    </div>
</footer>

    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.15)';
            } else {
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            }
        });
    </script>
</body>
</html>