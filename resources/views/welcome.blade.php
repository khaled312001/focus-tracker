<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Focus Tracker') }}</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/css/welcome.css', 'resources/js/app.js', 'resources/js/welcome.js'])
        <!-- AOS Animation Library -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
        <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
        <!-- Dark mode -->
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>
    </head>
    <body class="antialiased">
        <div class="welcome-container">
            <!-- Navigation -->
            @if (Route::has('login'))
                <nav class="welcome-nav">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-between h-16">
                            <div class="flex items-center">
                                <span class="text-xl font-bold gradient-text">Focus Tracker</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                @auth
                                    @if(auth()->user()->role === 'teacher')
                                        <a href="{{ route('teacher.dashboard') }}" class="cta-primary">
                                            Teacher Dashboard
                                        </a>
                                    @else
                                        <a href="{{ route('student.dashboard') }}" class="cta-primary">
                                            Student Dashboard
                                        </a>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="nav-link">
                                        Log in
                                    </a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="cta-primary">
                                            Get Started
                                        </a>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                </nav>
            @endif

            <!-- Hero Section -->
            <div class="hero-section">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center" data-aos="fade-up" data-aos-duration="1000">
                        <h1 class="hero-title">
                            Transform Your Learning Experience
                        </h1>
                        <p class="hero-subtitle">
                            Harness the power of AI to track focus, enhance engagement, and optimize learning outcomes in real-time.
                        </p>
                        <div class="cta-buttons">
                            <a href="{{ route('register') }}" class="cta-primary">
                                Start Free Trial
                            </a>
                            <a href="#features" class="cta-secondary">
                                Learn More
                            </a>
                        </div>
                    </div>
                    <div class="hero-image" data-aos="fade-up" data-aos-delay="300">
                        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1471&q=80" 
                             alt="Students learning" 
                             class="rounded-lg shadow-xl">
                    </div>
                </div>
            </div>

            <!-- Video Demo Section -->
            <div class="video-section py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-12" data-aos="fade-up">
                        <h2 class="section-title">See Focus Tracker in Action</h2>
                        <p class="section-subtitle">Watch how our AI-powered system enhances learning experiences</p>
                    </div>
                    <div class="video-container" data-aos="zoom-in">
                        <div class="video-wrapper">
                            <iframe class="w-full aspect-video rounded-lg shadow-xl" 
                                    src="https://www.youtube.com/embed/3q8thydY9bI" 
                                    title="Focus Tracker Demo" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                            <div class="video-fallback hidden">
                                <div class="fallback-content">
                                    <div class="fallback-icon">
                                        <svg class="w-16 h-16 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <h3 class="fallback-title">Video Not Available</h3>
                                    <p class="fallback-text">We're working on creating a demo video to showcase our focus tracking technology. In the meantime, you can learn more about our features below.</p>
                                    <a href="#features" class="cta-secondary">
                                        Explore Features
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div id="features" class="py-16 sm:py-24">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="feature-grid">
                        <!-- Student Card -->
                        <div class="feature-card" data-aos="fade-right">
                            <div class="flex flex-col h-full">
                                <div class="feature-image mb-6">
                                    <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1000&q=80" 
                                         alt="Student learning" 
                                         class="rounded-lg">
                                </div>
                                <div class="flex items-center mb-4">
                                    <div class="feature-icon">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                    <h2 class="feature-title">For Students</h2>
                                </div>
                                <p class="text-gray-600 dark:text-gray-300 mb-6 flex-grow">
                                    Track your focus levels during online classes and meetings. Get real-time feedback and improve your learning experience with our AI-powered focus tracking system.
                                </p>
                                <ul class="feature-list">
                                    <li class="feature-item">
                                        <svg class="feature-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Real-time focus monitoring
                                    </li>
                                    <li class="feature-item">
                                        <svg class="feature-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Personalized insights and reports
                                    </li>
                                    <li class="feature-item">
                                        <svg class="feature-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Focus improvement recommendations
                                    </li>
                                </ul>
                                <a href="{{ route('register') }}" class="cta-primary w-full text-center">
                                    Join as Student
                                </a>
                            </div>
                        </div>

                        <!-- Teacher Card -->
                        <div class="feature-card" data-aos="fade-left">
                            <div class="flex flex-col h-full">
                                <div class="feature-image mb-6">
                                    <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1000&q=80" 
                                         alt="Teacher teaching" 
                                         class="rounded-lg">
                                </div>
                                <div class="flex items-center mb-4">
                                    <div class="feature-icon">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <h2 class="feature-title">For Teachers</h2>
                                </div>
                                <p class="text-gray-600 dark:text-gray-300 mb-6 flex-grow">
                                    Monitor student engagement in real-time. Create and manage online meetings, track attendance, and analyze focus trends to optimize your teaching strategies.
                                </p>
                                <ul class="feature-list">
                                    <li class="feature-item">
                                        <svg class="feature-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Real-time student monitoring
                                    </li>
                                    <li class="feature-item">
                                        <svg class="feature-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Comprehensive analytics dashboard
                                    </li>
                                    <li class="feature-item">
                                        <svg class="feature-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Automated attendance tracking
                                    </li>
                                </ul>
                                <a href="{{ route('register') }}" class="cta-primary w-full text-center">
                                    Join as Teacher
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testimonials Section -->
            <section class="testimonials-section">
                <div class="testimonials-container">
                    <h2 class="section-title" data-aos="fade-up">What Our Users Say</h2>
                    <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Hear from students and teachers who have transformed their learning experience</p>
                    
                    <div class="testimonials-carousel" data-aos="fade-up" data-aos-delay="200">
                        <div class="testimonials-track">
                            @if($testimonials->count() > 0)
                                @foreach($testimonials as $testimonial)
                                <div class="testimonial-card">
                                    <div class="testimonial-content">
                                        <p class="testimonial-text">"{{ $testimonial->content }}"</p>
                                        <div class="testimonial-author">
                                            <img src="{{ $testimonial->avatar }}" alt="{{ $testimonial->name }}" class="testimonial-avatar">
                                            <div>
                                                <h4 class="testimonial-name">{{ $testimonial->name }}</h4>
                                                <p class="testimonial-role">{{ $testimonial->role }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="testimonial-card">
                                    <div class="testimonial-content">
                                        <p class="testimonial-text">"Focus Tracker has revolutionized how I monitor my students' engagement. The real-time analytics are invaluable!"</p>
                                        <div class="testimonial-author">
                                            <img src="https://randomuser.me/api/portraits/women/79.jpg" alt="Sarah Johnson" class="testimonial-avatar">
                                            <div>
                                                <h4 class="testimonial-name">Sarah Johnson</h4>
                                                <p class="testimonial-role">High School Teacher</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="testimonial-card">
                                    <div class="testimonial-content">
                                        <p class="testimonial-text">"The focus tracking feature has helped me understand my learning patterns and improve my study habits significantly."</p>
                                        <div class="testimonial-author">
                                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Michael Chen" class="testimonial-avatar">
                                            <div>
                                                <h4 class="testimonial-name">Michael Chen</h4>
                                                <p class="testimonial-role">University Student</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Navigation Arrows -->
                        <div class="testimonials-nav">
                            <button class="nav-arrow prev" aria-label="Previous testimonial">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button class="nav-arrow next" aria-label="Next testimonial">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Scroll Indicators -->
                        <div class="scroll-indicators">
                            @if($testimonials->count() > 0)
                                @foreach($testimonials as $index => $testimonial)
                                <div class="scroll-indicator {{ $index === 0 ? 'active' : '' }}"></div>
                                @endforeach
                            @else
                                <div class="scroll-indicator active"></div>
                                <div class="scroll-indicator"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="welcome-footer">
                <div class="footer-content">
                    <div class="footer-grid">
                        <div class="footer-column">
                            <h3 class="footer-title">About Focus Tracker</h3>
                            <p class="text-gray-400 mb-4">Empowering educators and students with AI-driven attention tracking technology to enhance learning outcomes.</p>
                            <div class="footer-social">
                                <a href="#" class="social-icon">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <a href="#" class="social-icon">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                                </a>
                                <a href="#" class="social-icon">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.535.223l.19-2.72 4.94-4.47c.217-.19-.047-.296-.334-.106l-6.104 3.853-2.523-.786c-.548-.176-.558-.548.12-.812l9.126-3.506c.458-.176.86.103.708.955z"/></svg>
                                </a>
                            </div>
                        </div>
                        <div class="footer-column">
                            <h3 class="footer-title">Quick Links</h3>
                            <a href="#features" class="footer-link">Features</a>
                            <a href="#testimonials" class="footer-link">Testimonials</a>
                            <a href="#pricing" class="footer-link">Pricing</a>
                            <a href="#contact" class="footer-link">Contact</a>
                        </div>
                        <div class="footer-column">
                            <h3 class="footer-title">Resources</h3>
                            <a href="#" class="footer-link">Documentation</a>
                            <a href="#" class="footer-link">Blog</a>
                            <a href="#" class="footer-link">Support</a>
                            <a href="#" class="footer-link">Privacy Policy</a>
                        </div>
                        <div class="footer-column">
                            <h3 class="footer-title">Contact Us</h3>
                            <p class="text-gray-400">Email: info@focustracker.com</p>
                            <p class="text-gray-400">Phone: +1 (555) 123-4567</p>
                            <p class="text-gray-400">Address: 123 Education St, Tech City</p>
                        </div>
                    </div>
                    <div class="footer-bottom">
                        <p>&copy; {{ date('Y') }} Focus Tracker. All rights reserved.</p>
                    </div>
                </div>
            </footer>
        </div>

        <script>
            // Initialize AOS
            AOS.init({
                duration: 1000,
                once: true,
                offset: 100
            });

            function scrollTestimonials(direction) {
                const container = document.querySelector('.testimonials-scroll');
                const scrollAmount = direction === 'left' ? -350 : 350;
                container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                updateScrollIndicators();
            }

            function updateScrollIndicators() {
                const container = document.querySelector('.testimonials-scroll');
                const indicators = document.querySelectorAll('.scroll-indicator');
                const scrollPercentage = container.scrollLeft / (container.scrollWidth - container.clientWidth);
                const activeIndex = Math.round(scrollPercentage * (indicators.length - 1));

                indicators.forEach((indicator, index) => {
                    indicator.classList.toggle('active', index === activeIndex);
                });
            }

            // Add scroll event listener
            document.addEventListener('DOMContentLoaded', function() {
                const scrollContainer = document.querySelector('.testimonials-scroll');
                if (scrollContainer) {
                    scrollContainer.addEventListener('scroll', updateScrollIndicators);
                }
            });
        </script>
    </body>
</html>
