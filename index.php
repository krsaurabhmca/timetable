<?php
require_once 'config.php';
// If already logged in, redirect to dashboard
$already_logged_in = !empty($_SESSION['org_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeGrid - Smart Academic Timetable Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4ade80;   /* Parrot Green */
            --primary-dark: #22c55e;
            --primary-soft: rgba(74, 222, 128, 0.1);
            --secondary: #000000; /* Black */
            --bg: #ffffff;
            --bg-alt: #f8fafc;
            --text-main: #000000;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-xl: 32px;
            --radius-lg: 20px;
            --whatsapp: #25d366;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; color: var(--text-main); line-height: 1.6; overflow-x: hidden; scroll-behavior: smooth; }
        h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }

        .container { max-width: 1240px; margin: 0 auto; padding: 0 1.5rem; width: 100%; }

        /* Navigation */
        nav { padding: 1rem 0; position: fixed; width: 100%; top: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); z-index: 1000; border-bottom: 1px solid rgba(226, 232, 240, 0.5); }
        .nav-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.4rem; font-weight: 800; color: var(--secondary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .logo span { color: var(--primary); }
        
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-links a:not(.btn) { text-decoration: none; color: var(--secondary); font-weight: 600; font-size: 0.9rem; display: none; } /* Hidden on mobile */
        
        .btn { padding: 0.75rem 1.25rem; border-radius: 12px; text-decoration: none; font-weight: 700; transition: all 0.3s; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.9rem; }
        .btn-primary { background: var(--primary); color: var(--secondary); }
        .btn-black { background: var(--secondary); color: var(--primary); }

        /* Hero Section */
        .hero { padding: 8rem 0 4rem; background: radial-gradient(circle at top right, rgba(74, 222, 128, 0.08) 0%, rgba(255, 255, 255, 1) 70%); }
        .hero-content { display: flex; flex-direction: column; gap: 3rem; text-align: center; }
        .hero-badge { display: inline-flex; align-items: center; gap: 6px; background: var(--primary-soft); color: var(--primary-dark); padding: 6px 12px; border-radius: 50px; font-weight: 700; font-size: 0.75rem; margin-bottom: 1.5rem; border: 1px solid rgba(74, 222, 128, 0.2); }
        .hero-text h1 { font-size: 2.5rem; line-height: 1.2; margin-bottom: 1.25rem; letter-spacing: -1px; color: var(--secondary); }
        .hero-text h1 span { color: var(--primary); }
        .hero-text p { font-size: 1.1rem; color: var(--text-muted); margin-bottom: 2rem; padding: 0 10px; }
        
        .hero-image { order: -1; } /* Image first on mobile */
        .hero-image img { width: 100%; border-radius: 16px; box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1); border: 4px solid white; }

        /* Stats Section */
        .stats-section { padding: 3rem 0; background: var(--secondary); color: #fff; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; text-align: center; }
        .stat-item h2 { font-size: 2rem; color: var(--primary); margin-bottom: 0.25rem; }
        .stat-item p { font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; opacity: 0.8; }

        /* Features */
        .features { padding: 5rem 0; background: #fff; }
        .section-header { text-align: center; margin-bottom: 3rem; }
        .section-header h2 { font-size: 2rem; margin-bottom: 1rem; color: var(--secondary); line-height: 1.2; }
        .feature-grid { display: flex; flex-direction: column; gap: 1.5rem; }
        .feature-card { background: white; padding: 2rem; border-radius: 24px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .feature-icon { width: 56px; height: 56px; background: var(--primary-soft); color: var(--primary-dark); display: flex; align-items: center; justify-content: center; border-radius: 16px; font-size: 1.5rem; margin-bottom: 1.5rem; }
        .feature-card h3 { margin-bottom: 0.75rem; font-size: 1.25rem; }
        .feature-card p { font-size: 0.95rem; }

        /* Process Steps */
        .process { padding: 5rem 0; background: var(--bg-alt); }
        .process-grid { display: flex; flex-direction: column; gap: 3rem; }
        .process-item { text-align: center; }
        .process-number { width: 48px; height: 48px; background: var(--primary); color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 800; margin: 0 auto 1.25rem; }
        .process-item h4 { font-size: 1.25rem; margin-bottom: 0.75rem; }

        /* Pricing Area */
        .pricing { padding: 5rem 0; background: #fff; text-align: center; }
        .pricing-card { margin: 0 auto; background: white; padding: 2.5rem 1.5rem; border-radius: 24px; box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08); border: 2px solid var(--primary); position: relative; }
        .price { font-size: 3.5rem; font-weight: 800; margin: 1rem 0; display: flex; align-items: baseline; justify-content: center; color: var(--secondary); }
        .price span { font-size: 1.25rem; color: var(--text-muted); margin-left: 5px; }
        .pricing-features { list-style: none; margin: 2rem 0; text-align: left; background: var(--bg-alt); padding: 1.5rem; border-radius: 16px; font-size: 0.9rem; }
        .pricing-features li { display: flex; align-items: center; gap: 10px; margin-bottom: 0.75rem; font-weight: 600; }
        .pricing-features i { color: #10b981; }

        /* FAQ Section */
        .faq { padding: 5rem 0; background: #fff; }
        .faq-grid { display: flex; flex-direction: column; gap: 1rem; }
        .faq-item { background: var(--bg-alt); padding: 1.5rem; border-radius: 16px; text-align: left; }
        .faq-item h4 { margin-bottom: 8px; color: var(--secondary); font-size: 1.1rem; line-height: 1.3; }
        .faq-item p { font-size: 0.95rem; }

        /* Contact Section */
        .cta-section { padding: 4rem 1.5rem; background: var(--secondary); color: white; border-radius: 32px; margin: 2rem 1rem; text-align: center; }
        .cta-section h2 { font-size: 1.75rem; margin-bottom: 1rem; line-height: 1.2; }
        .whatsapp-bubble { background: var(--whatsapp); color: white; padding: 0.85rem 1.5rem; border-radius: 50px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; font-size: 1rem; text-decoration: none; margin-top: 1.5rem; }
        
        .floating-wa { position: fixed; bottom: 20px; right: 20px; width: 56px; height: 56px; background: var(--whatsapp); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.75rem; z-index: 999; text-decoration: none; box-shadow: 0 8px 25px rgba(37, 211, 102, 0.3); }

        footer { padding: 4rem 0 2rem; background: #fff; text-align: center; }
        .footer-logo { font-size: 1.25rem; }
        .copyright { color: var(--text-muted); font-size: 0.8rem; margin-top: 1.5rem; }

        /* Desktop Adjustments */
        @media (min-width: 992px) {
            nav { padding: 1.25rem 0; }
            .logo { font-size: 1.6rem; }
            .nav-links { gap: 2.5rem; }
            .nav-links a:not(.btn) { display: block; }
            .btn { padding: 0.85rem 1.75rem; font-size: 0.95rem; }

            .hero { padding: 12rem 0 7rem; }
            .hero-content { flex-direction: row; text-align: left; gap: 4rem; }
            .hero-image { order: 1; flex: 1; }
            .hero-text { flex: 1.2; }
            .hero-text h1 { font-size: 4rem; }
            .hero-text p { font-size: 1.35rem; padding: 0; }
            .hero-image img { border-radius: var(--radius-lg); border: 8px solid white; }

            .stats-grid { grid-template-columns: repeat(4, 1fr); padding: 1rem 0; }
            .stats-section { padding: 4rem 0; }

            .features { padding: 8rem 0; }
            .section-header h2 { font-size: 2.8rem; }
            .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2.5rem; }

            .process { padding: 8rem 0; }
            .process-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3rem; }

            .pricing { padding: 8rem 0; }
            .pricing-card { max-width: 500px; padding: 4rem 3.5rem; }
            .price { font-size: 5rem; }

            .faq { padding: 8rem 0; }
            .faq-grid { max-width: 800px; margin: 0 auto; }
            
            .cta-section { padding: 6rem 0; border-radius: 48px; margin: 4rem 1.5rem; }
            .cta-section h2 { font-size: 3rem; }
        }
    </style>
</head>
<body>

    <a href="https://wa.me/919431426600" class="floating-wa" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <nav>
        <div class="container nav-content">
            <a href="#" class="logo">
                <i class="fas fa-calendar-alt"></i> TIME<span>GRID</span>
            </a>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#how">How it Works</a>
                <a href="#pricing">Pricing</a>
                <?php if ($already_logged_in): ?>
                    <a href="dashboard.php" class="btn btn-black" style="display:flex;"><i class="fas fa-th-large"></i> Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn" style="border: 2px solid var(--secondary); background: transparent; display: flex;">LOGIN</a>
                    <a href="register.php" class="btn btn-primary" style="display: flex;">FREE TRIAL</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container hero-content">
            <div class="hero-image">
                <img src="assets/images/hero_parrot_green.png" alt="TimeGrid Dashboard Preview">
            </div>
            <div class="hero-text">
                <div class="hero-badge"><i class="fas fa-sparkles"></i> 100% Conflict-Free Timetable</div>
                <h1>Manual Timetable Se Ho Pareshan? <span>TimeGrid Hai Na!</span></h1>
                <p>The ultimate AI-powered routine management system for schools and colleges. No more clashes, no more stress.</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-direction: column; align-items: center;">
                    <?php if ($already_logged_in): ?>
                        <a href="dashboard.php" class="btn btn-black" style="width: 100%; justify-content: center;"><i class="fas fa-th-large"></i> Go to Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-black" style="width: 100%; justify-content: center;">Start Free Trial <i class="fas fa-arrow-right"></i></a>
                        <a href="https://wa.me/919431426600" class="btn btn-primary" style="width: 100%; justify-content: center; background: transparent; border: 2px solid var(--secondary);"><i class="fab fa-whatsapp"></i> Book a Demo</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="stats-section">
        <div class="container stats-grid">
            <div class="stat-item">
                <h2>500+</h2>
                <p>Schools</p>
            </div>
            <div class="stat-item">
                <h2>10k+</h2>
                <p>Timetables</p>
            </div>
            <div class="stat-item">
                <h2>100%</h2>
                <p>Conflict Free</p>
            </div>
            <div class="stat-item">
                <h2>24/7</h2>
                <p>Support</p>
            </div>
        </div>
    </div>

    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Powerful Features</h2>
                <p>Smart academic management.</p>
            </div>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h3>AI Generation</h3>
                    <p>Intelligent engine handles teacher limits automatically. Ghanto ka kaam seconds mein!</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-user-shield"></i></div>
                    <h3>Conflict Lock</h3>
                    <p>Prevention of faculty overlaps. Impossible to double-book staff or rooms again.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-file-pdf"></i></div>
                    <h3>Pro Exports</h3>
                    <p>Beautiful, print-ready PDF schedules. Bas ek click aur print ready!</p>
                </div>
            </div>
        </div>
    </section>

    <section class="process" id="how">
        <div class="container">
            <div class="section-header">
                <h2>3 Simple Steps</h2>
                <p>Setting up is extremely easy.</p>
            </div>
            <div class="process-grid">
                <div class="process-item">
                    <div class="process-number">1</div>
                    <h4>Enter Data</h4>
                    <p>Add your Teachers, Subjects, and Classes.</p>
                </div>
                <div class="process-item">
                    <div class="process-number">2</div>
                    <h4>Set Rules</h4>
                    <p>Assign subjects to teachers and set limits.</p>
                </div>
                <div class="process-item">
                    <div class="process-number">3</div>
                    <h4>Click Generate</h4>
                    <p>Hit the button and get your routine.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pricing" id="pricing">
        <div class="container">
            <div class="pricing-card">
                <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--secondary);">INSTITUTIONAL PRO</h3>
                <div class="price">
                    <span class="price-currency">₹</span>2999<span>/ yr</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check-circle"></i> Unlimited Classes</li>
                    <li><i class="fas fa-check-circle"></i> Unlimited Teachers</li>
                    <li><i class="fas fa-check-circle"></i> Substitution Manager</li>
                    <li><i class="fas fa-check-circle"></i> Excel/PDF Reports</li>
                </ul>
                <a href="register.php" class="btn btn-black" style="width: 100%; justify-content: center;">Get Started Now</a>
            </div>
        </div>
    </section>

    <section class="faq" id="faq">
        <div class="container">
            <div class="section-header">
                <h2>Got Questions?</h2>
            </div>
            <div class="faq-grid">
                <div class="faq-item">
                    <h4>Excel se data import hoga?</h4>
                    <p>Yes! Aap teachers aur subjects ki list direct upload kar sakte hain.</p>
                </div>
                <div class="faq-item">
                    <h4>Payment kaise karein?</h4>
                    <p>Aap UPI, Debit Card, ya Net Banking se online pay kar sakte hain.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="container">
        <div class="cta-section">
            <h2>Abhi Bhi Doubt Hai?</h2>
            <p>Directly humse baat kijiye! Join 500+ schools today.</p>
            <a href="https://wa.me/919431426600" class="whatsapp-bubble" target="_blank">
                <i class="fab fa-whatsapp"></i> Chat on WhatsApp
            </a>
            <div style="margin-top: 1.5rem; font-weight: 800; font-size: 1.2rem;">CALL: +91 9431426600</div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="logo" style="justify-content: center; margin-bottom: 1.5rem;">
                <i class="fas fa-calendar-alt"></i> TIME<span>GRID</span>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> TIMEGRID. BY OFFERPLANT.
            </div>
        </div>
    </footer>

</body>
</html>
