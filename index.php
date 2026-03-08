<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeGrid - Aapka Apna Smart Timetable Maker</title>
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

        .container { max-width: 1240px; margin: 0 auto; padding: 0 1.5rem; }

        /* Navigation */
        nav { padding: 1.25rem 0; position: fixed; width: 100%; top: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); z-index: 1000; border-bottom: 1px solid rgba(226, 232, 240, 0.5); }
        .nav-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.6rem; font-weight: 800; color: var(--secondary); text-decoration: none; display: flex; align-items: center; gap: 0.65rem; }
        .logo span { color: var(--primary); }
        .nav-links { display: flex; gap: 2.5rem; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--secondary); font-weight: 600; font-size: 0.95rem; }
        
        .btn { padding: 0.85rem 1.75rem; border-radius: 14px; text-decoration: none; font-weight: 700; transition: all 0.3s; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: var(--secondary); box-shadow: 0 10px 25px -5px rgba(74, 222, 128, 0.3); }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-black { background: var(--secondary); color: var(--primary); }

        /* Hero Section */
        .hero { padding: 12rem 0 7rem; background: radial-gradient(circle at top right, rgba(74, 222, 128, 0.08) 0%, rgba(255, 255, 255, 1) 70%); }
        .hero-content { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 4rem; align-items: center; }
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; background: var(--primary-soft); color: var(--primary-dark); padding: 8px 16px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; margin-bottom: 2rem; border: 1px solid rgba(74, 222, 128, 0.2); }
        .hero-text h1 { font-size: 4rem; line-height: 1.1; margin-bottom: 1.5rem; letter-spacing: -1.5px; color: var(--secondary); }
        .hero-text h1 span { color: var(--primary); }
        .hero-text p { font-size: 1.35rem; color: var(--text-muted); margin-bottom: 2.5rem; }
        
        .hero-image img { width: 100%; border-radius: var(--radius-lg); box-shadow: 0 40px 80px -15px rgba(0, 0, 0, 0.15); border: 8px solid white; }

        /* Features */
        .features { padding: 8rem 0; background: #fff; }
        .section-header { text-align: center; margin-bottom: 5rem; }
        .section-header h2 { font-size: 2.8rem; margin-bottom: 1.25rem; color: var(--secondary); }
        .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2.5rem; }
        .feature-card { background: white; padding: 3rem 2.5rem; border-radius: 30px; transition: all 0.4s; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .feature-card:hover { transform: translateY(-12px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border-color: var(--primary); }
        .feature-icon { width: 68px; height: 68px; background: var(--primary-soft); color: var(--primary-dark); display: flex; align-items: center; justify-content: center; border-radius: 20px; font-size: 1.75rem; margin-bottom: 2rem; }
        .feature-card h3 { margin-bottom: 1rem; font-size: 1.5rem; }

        /* Pricing Area */
        .pricing { padding: 8rem 0; background: var(--bg-alt); text-align: center; }
        .pricing-card { max-width: 500px; margin: 0 auto; background: white; padding: 4rem 3.5rem; border-radius: 40px; box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.12); border: 2px solid var(--primary); position: relative; }
        .price { font-size: 5rem; font-weight: 800; margin: 1.5rem 0; display: flex; align-items: baseline; justify-content: center; color: var(--secondary); }
        .price span { font-size: 1.75rem; color: var(--text-muted); margin-left: 8px; }
        .pricing-features { list-style: none; margin: 3rem 0; text-align: left; background: var(--bg-alt); padding: 2rem; border-radius: 24px; }
        .pricing-features li { display: flex; align-items: center; gap: 12px; margin-bottom: 1rem; font-weight: 600; }
        .pricing-features i { color: #10b981; }

        /* Contact Section */
        .cta-section { padding: 6rem 0; background: var(--secondary); color: white; border-radius: 48px; margin: 4rem 1.5rem; text-align: center; }
        .whatsapp-bubble { background: var(--whatsapp); color: white; padding: 1rem 2rem; border-radius: 50px; font-weight: 700; display: inline-flex; align-items: center; gap: 10px; font-size: 1.2rem; text-decoration: none; margin-top: 2rem; transition: transform 0.3s; }
        .whatsapp-bubble:hover { transform: scale(1.05); }
        
        .floating-wa { position: fixed; bottom: 30px; right: 30px; width: 68px; height: 68px; background: var(--whatsapp); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2.25rem; z-index: 999; text-decoration: none; box-shadow: 0 10px 40px rgba(37, 211, 102, 0.4); }

        footer { padding: 6rem 0 3rem; background: #fff; text-align: center; }
        .copyright { color: var(--text-muted); font-size: 0.9rem; margin-top: 2rem; }

        @media (max-width: 992px) {
            .hero-content { grid-template-columns: 1fr; text-align: center; }
            .hero-text h1 { font-size: 3rem; }
            .feature-grid { grid-template-columns: 1fr; }
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
                <a href="#features">Kyu Chune?</a>
                <a href="#pricing">Price</a>
                <a href="login.php">Login</a>
                <a href="register.php" class="btn btn-primary">Free Trial</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                <div class="hero-badge"><i class="fas fa-check"></i> 100% Tension Free Timetable</div>
                <h1>Manual Timetable Se Ho Pareshan? <span>TimeGrid Hai Na!</span></h1>
                <p>Ab school routine banana hoga super easy! No more clashes, no more headache. AI karega aapka saara kaam seconds mein.</p>
                <div style="display: flex; gap: 1.25rem; justify-content: center;">
                    <a href="register.php" class="btn btn-black">FREE TRIAL START KAREIN</a>
                    <a href="https://wa.me/919431426600" class="btn btn-primary"><i class="fab fa-whatsapp"></i> DEMO DEKHEIN</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="assets/images/hero_parrot_green.png" alt="TimeGrid Dashboard Preview">
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>TimeGrid Hi Kyu Use Karein?</h2>
                <p>Bas kuch simple steps mein banaiye best routine bina kisi mistake ke.</p>
            </div>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Super Fast Generation</h3>
                    <p> घंटो का काम बस कुछ seconds में! Hamara smart AI apne aap teacher aur subjects ko best slots mein fix kar deta hai.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-hand-paper"></i></div>
                    <h3>Clash Khatam!</h3>
                    <p>Ek hi time par do teachers ya do classes? Never! Hamara system har clash ko ane se pehle hi rok leta hai.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-file-pdf"></i></div>
                    <h3>Ek Click Mein Print</h3>
                    <p>Sabhi classes aur teachers ke liye alag-alag, sundar PDF routines. Bas generate kijiye aur print nikal lijiye.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pricing" id="pricing">
        <div class="container">
            <div class="pricing-card">
                <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--secondary);">POORA SCHOOL PACKAGE</h3>
                <div class="price">
                    <span class="price-currency">₹</span>2999<span>/ Saal</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check-circle"></i> Unlimited Classes aur Sections</li>
                    <li><i class="fas fa-check-circle"></i> Unlimited Teachers aur Staff</li>
                    <li><i class="fas fa-check-circle"></i> Substitution/Adj. Management</li>
                    <li><i class="fas fa-check-circle"></i> Excel aur PDF Reports</li>
                </ul>
                <a href="register.php" class="btn btn-black" style="display: flex; justify-content: center; width: 100%;">AAJ HI START KAREIN</a>
            </div>
        </div>
    </section>

    <section class="container">
        <div class="cta-section">
            <h2>Abhi Bhi Doubt Hai?</h2>
            <p>Directly humse baat kijiye! Hum aapko poora setup karke denge aur demo bhi dikhayenge.</p>
            <a href="https://wa.me/919431426600" class="whatsapp-bubble" target="_blank">
                <i class="fab fa-whatsapp"></i> WhatsApp Par Message Karein: 9431426600
            </a>
            <div style="margin-top: 1.5rem; font-weight: 800; font-size: 1.4rem;">CALL KAREIN: +91 9431426600</div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <i class="fas fa-calendar-alt"></i> Aapka Apna <span>TIMEGRID</span>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> TIMEGRID. MADE BY OFFERPLANT WITH LOVE.
            </div>
        </div>
    </footer>

</body>
</html>
