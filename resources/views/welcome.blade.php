<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R&D Finance - QuickBooks Integration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #8B0000;
            --secondary-color: #FF4136;
            --text-color: #FFFFFF;
            --background-color: #000000;
            --accent-color: #333333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Navbar Styles */
        .navbar {
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: background-color 0.3s ease;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li {
            margin-left: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        /* Hero Styles */
        .hero {
            padding: 8rem 0 4rem;
            background: linear-gradient(135deg, var(--background-color), var(--accent-color));
        }

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .hero-text {
            flex: 1;
            padding-right: 2rem;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.8;
        }

        .cta-button {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--text-color);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(139, 0, 0, 0.3);
        }

        .hero-image {
            flex: 1;
            text-align: right;
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            /* box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); */
        }

        /* Features Section */
        .features {
            padding: 4rem 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background-color: var(--accent-color);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .feature-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Testimonial Section */
        .testimonial {
            padding: 4rem 0;
            background-color: var(--accent-color);
        }

        .testimonial-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .testimonial-text {
            font-size: 1.5rem;
            font-style: italic;
            margin-bottom: 2rem;
        }

        .testimonial-author {
            font-weight: bold;
        }

        /* FAQ Section */
        .faq {
            padding: 4rem 0;
        }

        .faq-item {
            margin-bottom: 1.5rem;
        }

        .faq-question {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            cursor: pointer;
        }

        .faq-answer {
            display: none;
            padding-left: 1rem;
            border-left: 2px solid var(--primary-color);
        }

        /* Footer Styles */
        .footer {
            background-color: var(--accent-color);
            color: var(--text-color);
            padding: 2rem 0;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-links {
            display: flex;
            list-style: none;
        }

        .footer-links li {
            margin-right: 1rem;
        }

        .footer-links a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
            }

            .nav-links {
                margin-top: 1rem;
            }

            .nav-links li {
                margin-left: 1rem;
                margin-right: 1rem;
            }

            .hero-content {
                flex-direction: column;
            }

            .hero-text, .hero-image {
                text-align: center;
                padding: 0;
                margin-bottom: 2rem;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }

            .footer-links {
                margin-top: 1rem;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container navbar-container">
            <a href="#" class="logo">R&D Finance</a>
            <ul class="nav-links">
                {{--
                <li><a href="#features">Features</a></li>
                <li><a href="#testimonial">Testimonial</a></li>
                <li><a href="#faq">FAQ</a></li>
                --}}
                <li><a href="{{route("login")}}">Login</a></li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                <h1>Revolutionize Your Finances with QuickBooks Integration</h1>
                <p>Seamlessly manage your business finances and accounting with our powerful QuickBooks-integrated app.</p>
                <a href="{{route("register")}}" class="cta-button">SignUp Now</a>
            </div>
            <div class="hero-image">
                <img src="{{asset("img\logo.png")}}" alt="">
            </div>
        </div>
    </header>

    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Powerful Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="feature-icon fas fa-sync-alt"></i>
                    <h3 class="feature-title">Real-time Sync</h3>
                    <p>Instantly sync your data with QuickBooks for up-to-date financial insights.</p>
                </div>
                <div class="feature-card">
                    <i class="feature-icon fas fa-chart-line"></i>
                    <h3 class="feature-title">Advanced Analytics</h3>
                    <p>Gain valuable insights with our advanced financial analytics and reporting tools.</p>
                </div>
                <div class="feature-card">
                    <i class="feature-icon fas fa-lock"></i>
                    <h3 class="feature-title">Bank-level Security</h3>
                    <p>Rest easy knowing your financial data is protected with state-of-the-art security measures.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="testimonial" class="testimonial">
        <div class="container">
            <div class="testimonial-content">
                <p class="testimonial-text">"R&D Finance has transformed the way we manage our finances. The QuickBooks integration is seamless, and the insights we've gained have been invaluable for our business growth."</p>
                {{-- <p class="testimonial-author">- Nola, Senior at Ctrl-c Ctrl-v Department.</p> --}}
            </div>
        </div>
    </section>

    <section id="faq" class="faq">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-item">
                <h3 class="faq-question">How does the QuickBooks integration work?</h3>
                <p class="faq-answer">Our app seamlessly connects with your QuickBooks account, allowing for real-time data synchronization and comprehensive financial management.</p>
            </div>
            <div class="faq-item">
                <h3 class="faq-question">Is my financial data secure?</h3>
                <p class="faq-answer">Absolutely. We employ bank-level security measures to ensure your data is encrypted and protected at all times.</p>
            </div>
            {{-- <div class="faq-item">
                <h3 class="faq-question">Can I try FinanceFlow before purchasing?</h3>
                <p class="faq-answer">Yes! We offer a 14-day free trial so you can experience the full power of FinanceFlow risk-free.</p>
            </div> --}}
        </div>
    </section>

    <footer class="footer">
        <div class="container footer-content">
            <p>&copy; 2025 R&D Finance. All rights reserved.</p>
            <ul class="footer-links">
                <li><a href="{{route("privacy-policy")}}">Privacy Policy</a></li>
                <li><a href="{{route("eula")}}">End-User License Agreement</a></li>
                {{-- <li><a href="#">Contact Us</a></li> --}}
            </ul>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.backgroundColor = 'rgba(0, 0, 0, 0.9)';
            } else {
                navbar.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            }
        });

        // FAQ toggle
        const faqQuestions = document.querySelectorAll('.faq-question');
        faqQuestions.forEach(question => {
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                answer.style.display = answer.style.display === 'block' ? 'none' : 'block';
            });
        });

        // Add a simple animation to the CTA button
        const ctaButton = document.querySelector('.cta-button');
        ctaButton.addEventListener('mouseover', () => {
            ctaButton.style.transform = 'scale(1.05)';
        });
        ctaButton.addEventListener('mouseout', () => {
            ctaButton.style.transform = 'scale(1)';
        });

        // Animate feature cards on scroll
        const featureCards = document.querySelectorAll('.feature-card');
        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        featureCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>

