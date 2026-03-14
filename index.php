<?php
require_once 'config.php';
$already_logged_in = !empty($_SESSION['org_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeGrid — Smart Academic Timetable System | Conflict-Free, Auto-Generated</title>
    <meta name="description" content="TimeGrid automates school timetable creation. 100% conflict-free, AI-powered, print-ready PDF export. Used by 500+ schools. 14-day free trial.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    :root {
        --green:      #4ade80;
        --green-dark: #22c55e;
        --green-glow: rgba(74,222,128,0.15);
        --black:      #000000;
        --navy:       #0f172a;
        --navy2:      #1e293b;
        --white:      #ffffff;
        --grey:       #64748b;
        --border:     #e2e8f0;
        --wa:         #25d366;
    }

    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    html { scroll-behavior: smooth; }
    body { font-family:'Outfit',sans-serif; color:var(--black); overflow-x:hidden; line-height:1.6; background:#fff; }
    h1,h2,h3,h4 { font-family:'Plus Jakarta Sans',sans-serif; }
    a { text-decoration:none; }
    .container { max-width:1200px; margin:0 auto; padding:0 1.5rem; width:100%; }

    /* ── NAVBAR ───────────────────────────────────────── */
    nav {
        position:fixed; top:0; width:100%; z-index:1000;
        padding:0.9rem 0;
        background:rgba(255,255,255,0.92);
        backdrop-filter:blur(20px);
        border-bottom:1px solid rgba(0,0,0,0.07);
        transition:box-shadow 0.3s;
    }
    nav.scrolled { box-shadow:0 4px 24px rgba(0,0,0,0.08); }
    .nav-inner { display:flex; justify-content:space-between; align-items:center; }
    .logo { font-size:1.5rem; font-weight:900; color:var(--black); display:flex; align-items:center; gap:8px; letter-spacing:-0.5px; }
    .logo i { color:var(--green); font-size:1.2rem; }
    .logo span { color:var(--green); }
    .nav-links { display:flex; align-items:center; gap:2rem; }
    .nav-links a.link { color:var(--black); font-weight:600; font-size:0.9rem; transition:color 0.2s; display:none; }
    .nav-links a.link:hover { color:var(--green-dark); }
    .btn {
        display:inline-flex; align-items:center; gap:6px;
        padding:0.65rem 1.4rem; border-radius:10px;
        font-weight:700; font-size:0.9rem; cursor:pointer;
        border:none; transition:all 0.25s; text-decoration:none;
    }
    .btn-green  { background:var(--green); color:var(--black); }
    .btn-green:hover  { background:var(--green-dark); transform:translateY(-1px); box-shadow:0 6px 20px rgba(74,222,128,0.35); }
    .btn-black  { background:var(--black); color:var(--green); }
    .btn-black:hover  { background:#1e293b; transform:translateY(-1px); box-shadow:0 6px 20px rgba(0,0,0,0.2); }
    .btn-outline { background:transparent; color:var(--black); border:2px solid var(--black); }
    .btn-outline:hover { background:var(--black); color:var(--green); }
    .btn-lg { padding:1rem 2.2rem; font-size:1rem; border-radius:14px; }
    .btn-wa { background:var(--wa); color:#fff; }
    .btn-wa:hover { background:#1ebe57; transform:translateY(-1px); box-shadow:0 6px 20px rgba(37,211,102,0.35); }

    /* ── HERO ─────────────────────────────────────────── */
    .hero {
        padding:9rem 0 5rem;
        background: radial-gradient(ellipse 80% 60% at 60% 0%, rgba(74,222,128,0.12) 0%, #fff 65%);
        position:relative; overflow:hidden;
    }
    .hero::before {
        content:'';
        position:absolute; inset:0;
        background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%234ade80' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        pointer-events:none;
    }
    .hero-inner { display:flex; flex-direction:column; align-items:center; text-align:center; gap:2rem; position:relative; }
    .hero-badge {
        display:inline-flex; align-items:center; gap:8px;
        background:#f0fdf4; color:var(--green-dark);
        border:1px solid #bbf7d0; border-radius:50px;
        padding:6px 16px; font-size:0.8rem; font-weight:700;
        animation:fadeDown 0.6s ease both;
    }
    .hero-badge .dot { width:6px;height:6px;border-radius:50%;background:var(--green);animation:pulse 1.5s infinite; }
    .hero-title {
        font-size:clamp(2.4rem, 6vw, 4.2rem); font-weight:900;
        line-height:1.15; letter-spacing:-2px; color:var(--black);
        animation:fadeUp 0.7s 0.1s ease both;
    }
    .hero-title .hl { color:var(--green-dark); position:relative; }
    .hero-title .hl::after {
        content:''; position:absolute; bottom:-4px; left:0; right:0;
        height:4px; background:var(--green); border-radius:2px;
    }
    .hero-sub {
        max-width:600px; font-size:1.15rem; color:var(--grey);
        font-weight:500; animation:fadeUp 0.7s 0.2s ease both; line-height:1.7;
    }
    .hero-ctas { display:flex; gap:1rem; flex-wrap:wrap; justify-content:center; animation:fadeUp 0.7s 0.3s ease both; }
    .hero-stats {
        display:flex; gap:2.5rem; flex-wrap:wrap; justify-content:center;
        animation:fadeUp 0.7s 0.4s ease both;
        padding-top:1rem; border-top:1px solid var(--border);
    }
    .hero-stat { text-align:center; }
    .hero-stat strong { display:block; font-size:1.6rem; font-weight:900; color:var(--black); }
    .hero-stat span { font-size:0.78rem; color:var(--grey); font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }

    /* ── MARQUEE STRIP ────────────────────────────────── */
    .strip { background:var(--black); padding:0.9rem 0; overflow:hidden; }
    .strip-track { display:flex; gap:3rem; animation:marquee 25s linear infinite; white-space:nowrap; }
    .strip-item { color:var(--green); font-weight:700; font-size:0.85rem; display:flex; align-items:center; gap:8px; flex-shrink:0; }
    .strip-item i { color:#fff; }
    @keyframes marquee { from{transform:translateX(0)} to{transform:translateX(-50%)} }

    /* ── SECTION COMMONS ──────────────────────────────── */
    section { padding:5rem 0; }
    .sec-label { font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:var(--green-dark); margin-bottom:0.75rem; display:block; }
    .sec-title { font-size:clamp(1.8rem,4vw,2.6rem); font-weight:800; line-height:1.2; color:var(--black); letter-spacing:-1px; margin-bottom:1rem; }
    .sec-sub { font-size:1rem; color:var(--grey); max-width:560px; line-height:1.7; }
    .text-center { text-align:center; }
    .text-center .sec-sub { margin:0 auto; }

    /* ── FEATURES ─────────────────────────────────────── */
    .features { background:#fff; }
    .feat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1.5rem; margin-top:3rem; }
    .feat-card {
        padding:1.75rem; border-radius:20px;
        border:1.5px solid var(--border);
        background:#fff; transition:all 0.25s;
        position:relative; overflow:hidden;
    }
    .feat-card::before {
        content:''; position:absolute; top:0; left:0; right:0; height:3px;
        background:linear-gradient(90deg, var(--green), var(--green-dark));
        opacity:0; transition:opacity 0.25s;
    }
    .feat-card:hover { border-color:var(--green); transform:translateY(-4px); box-shadow:0 12px 40px rgba(74,222,128,0.12); }
    .feat-card:hover::before { opacity:1; }
    .feat-icon {
        width:52px;height:52px;border-radius:14px;
        background:linear-gradient(135deg,#f0fdf4,#dcfce7);
        display:flex;align-items:center;justify-content:center;
        font-size:1.3rem; color:var(--green-dark); margin-bottom:1.1rem;
    }
    .feat-card h3 { font-size:1rem; font-weight:800; margin-bottom:0.4rem; color:var(--black); }
    .feat-card p { font-size:0.85rem; color:var(--grey); line-height:1.65; }

    /* ── HOW IT WORKS ─────────────────────────────────── */
    .how { background:#f8fafc; }
    .steps { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:1.5rem; margin-top:3rem; }
    .step-card { text-align:center; padding:2rem 1.5rem; }
    .step-num {
        width:56px;height:56px;border-radius:50%;
        background:var(--black); color:var(--green);
        display:flex;align-items:center;justify-content:center;
        font-size:1.4rem;font-weight:900;margin:0 auto 1.25rem;
        box-shadow:0 8px 24px rgba(0,0,0,0.15);
    }
    .step-card h4 { font-size:1.05rem; font-weight:800; margin-bottom:0.5rem; }
    .step-card p { font-size:0.85rem; color:var(--grey); line-height:1.65; }
    .step-connector { display:none; }

    /* ── VIEW MODES ───────────────────────────────────── */
    .views { background:#fff; }
    .view-cards { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; margin-top:2.5rem; }
    .view-card {
        padding:1.5rem; border-radius:16px; border:1.5px solid var(--border);
        display:flex; align-items:flex-start; gap:1rem; transition:all 0.22s;
        background:#fff;
    }
    .view-card:hover { border-color:var(--green); box-shadow:0 8px 30px rgba(74,222,128,0.1); transform:translateY(-2px); }
    .view-icon { width:46px;height:46px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.2rem; }
    .view-card h4 { font-size:0.95rem; font-weight:800; margin-bottom:4px; }
    .view-card p { font-size:0.8rem; color:var(--grey); line-height:1.55; }

    /* ── DEMO STRIP ───────────────────────────────────── */
    .demo-strip {
        background:var(--black); color:#fff; border-radius:28px;
        margin:2rem auto; padding:3.5rem 3rem;
        display:flex; align-items:center; justify-content:space-between;
        gap:2rem; flex-wrap:wrap;
    }
    .demo-strip h3 { font-size:1.6rem; font-weight:800; color:#fff; margin-bottom:0.5rem; }
    .demo-strip p { color:#94a3b8; font-size:0.95rem; }
    .demo-steps { display:flex; gap:1.25rem; flex-wrap:wrap; margin-top:1.25rem; }
    .demo-step {
        display:flex; align-items:center; gap:8px;
        background:rgba(74,222,128,0.1); border:1px solid rgba(74,222,128,0.25);
        border-radius:8px; padding:8px 14px; font-size:0.82rem; font-weight:600; color:var(--green);
    }
    .demo-step span.n { background:var(--green);color:var(--black);width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:900; }

    /* ── PRICING ──────────────────────────────────────── */
    .pricing { background:#f8fafc; }
    .price-card {
        max-width:500px; margin:3rem auto 0;
        background:#fff; border-radius:28px; padding:3rem;
        border:2px solid var(--green);
        box-shadow:0 24px 64px rgba(74,222,128,0.12);
        position:relative; overflow:hidden;
    }
    .price-card::after {
        content:'MOST POPULAR';
        position:absolute; top:22px; right:-30px;
        background:var(--green); color:var(--black);
        font-size:0.65rem; font-weight:900; letter-spacing:0.1em;
        padding:5px 40px; transform:rotate(35deg); transform-origin:center; white-space:nowrap;
    }
    .price-label { font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:var(--grey); margin-bottom:1rem; }
    .price-name { font-size:1.5rem; font-weight:900; margin-bottom:0.5rem; }
    .price-amount { font-size:3.8rem; font-weight:900; line-height:1; color:var(--black); }
    .price-amount sup { font-size:1.5rem; vertical-align:top; margin-top:0.8rem; }
    .price-amount sub { font-size:1rem; color:var(--grey); font-weight:500; }
    .price-list { list-style:none; margin:2rem 0; display:flex; flex-direction:column; gap:0.75rem; }
    .price-list li { display:flex; align-items:center; gap:10px; font-size:0.9rem; font-weight:600; }
    .price-list li i { color:var(--green-dark); width:20px; }
    .trial-note { text-align:center; margin-top:1rem; font-size:0.82rem; color:var(--grey); }

    /* ── TESTIMONIAL / TRUST ──────────────────────────── */
    .trust { background:#fff; }
    .trust-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1.5rem; margin-top:3rem; }
    .trust-card {
        padding:1.75rem; background:#f8fafc; border-radius:18px;
        border:1px solid var(--border);
    }
    .trust-stars { color:#f59e0b; font-size:0.85rem; margin-bottom:0.75rem; }
    .trust-text { font-size:0.88rem; color:#334155; line-height:1.7; margin-bottom:1rem; font-style:italic; }
    .trust-author { display:flex; align-items:center; gap:10px; }
    .trust-avatar { width:38px;height:38px;border-radius:50%;background:var(--black);color:var(--green);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem; }
    .trust-name { font-size:0.85rem; font-weight:700; }
    .trust-role { font-size:0.75rem; color:var(--grey); }

    /* ── FAQ ──────────────────────────────────────────── */
    .faq { background:#f8fafc; }
    .faq-list { max-width:760px; margin:3rem auto 0; display:flex; flex-direction:column; gap:1rem; }
    .faq-item { background:#fff; border-radius:14px; border:1px solid var(--border); overflow:hidden; }
    .faq-q {
        width:100%;text-align:left;padding:1.25rem 1.5rem;
        background:none;border:none;cursor:pointer;
        display:flex;justify-content:space-between;align-items:center;
        font-family:'Plus Jakarta Sans',sans-serif;font-size:0.95rem;font-weight:700;
        color:var(--black); gap:1rem;
    }
    .faq-q i { color:var(--green-dark); transition:transform 0.3s; flex-shrink:0; }
    .faq-q.open i { transform:rotate(45deg); }
    .faq-a { max-height:0; overflow:hidden; transition:max-height 0.35s ease, padding 0.3s; font-size:0.88rem; color:var(--grey); line-height:1.7; }
    .faq-a.open { max-height:200px; padding:0 1.5rem 1.25rem; }

    /* ── CTA SECTION ──────────────────────────────────── */
    .cta-sec {
        background:var(--black); margin:3rem 1rem 2rem; border-radius:32px;
        padding:5rem 2rem; text-align:center; overflow:hidden; position:relative;
    }
    .cta-sec::before {
        content:''; position:absolute; top:-60px; left:50%; transform:translateX(-50%);
        width:500px;height:500px;border-radius:50%;
        background:radial-gradient(circle,rgba(74,222,128,0.15) 0%,transparent 70%);
        pointer-events:none;
    }
    .cta-sec h2 { font-size:clamp(1.8rem,4vw,2.8rem); font-weight:900; color:#fff; margin-bottom:1rem; letter-spacing:-1px; }
    .cta-sec p { color:#94a3b8; font-size:1rem; margin-bottom:2rem; max-width:500px; margin-left:auto; margin-right:auto; }
    .cta-btns { display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; }

    /* ── FOOTER ───────────────────────────────────────── */
    footer { background:#fff; border-top:1px solid var(--border); padding:3rem 0 2rem; }
    .footer-inner { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.5rem; }
    .footer-logo { font-size:1.3rem; font-weight:900; display:flex; align-items:center; gap:8px; }
    .footer-logo span { color:var(--green); }
    .footer-links { display:flex; gap:1.5rem; flex-wrap:wrap; }
    .footer-links a { font-size:0.85rem; color:var(--grey); font-weight:600; transition:color 0.2s; }
    .footer-links a:hover { color:var(--green-dark); }
    .footer-copy { font-size:0.78rem; color:var(--grey); }

    /* ── FLOATING WA ──────────────────────────────────── */
    .wa-fab {
        position:fixed;bottom:24px;right:24px;z-index:999;
        width:58px;height:58px;border-radius:50%;background:var(--wa);
        display:flex;align-items:center;justify-content:center;
        color:#fff;font-size:1.8rem;text-decoration:none;
        box-shadow:0 8px 28px rgba(37,211,102,0.4);
        animation:popIn 0.5s 1s both;
        transition:transform 0.2s, box-shadow 0.2s;
    }
    .wa-fab:hover { transform:scale(1.1); box-shadow:0 12px 36px rgba(37,211,102,0.5); }

    /* ── ANIMATIONS ───────────────────────────────────── */
    @keyframes fadeUp   { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
    @keyframes fadeDown { from{opacity:0;transform:translateY(-14px)} to{opacity:1;transform:translateY(0)} }
    @keyframes pulse    { 0%,100%{opacity:1} 50%{opacity:0.4} }
    @keyframes popIn    { from{opacity:0;transform:scale(0.5)} to{opacity:1;transform:scale(1)} }

    /* ── RESPONSIVE ───────────────────────────────────── */
    @media(min-width:768px) {
        .nav-links a.link { display:block; }
        .hero-inner { flex-direction:row; justify-content:center; text-align:left; }
        .hero-stats { justify-content:flex-start; }
        .hero-ctas  { justify-content:flex-start; }
        .hero-badge { align-self:flex-start; }
    }
    @media(min-width:1024px) {
        .feat-grid { grid-template-columns:repeat(3,1fr); }
        .steps { grid-template-columns:repeat(4,1fr); }
        .view-cards { grid-template-columns:repeat(4,1fr); }
    }
    @media(max-width:600px) {
        .view-cards { grid-template-columns:1fr; }
        .demo-strip { text-align:center; justify-content:center; }
        .cta-sec { margin:2rem 0.5rem 1rem; border-radius:20px; }
    }
    </style>
</head>
<body>

<!-- Floating WhatsApp -->
<a href="https://wa.me/919431426600" class="wa-fab" target="_blank" title="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- ═══ NAVBAR ═══════════════════════════════════════════ -->
<nav id="navbar">
    <div class="container nav-inner">
        <a href="#" class="logo"><i class="fas fa-calendar-alt"></i> TIME<span>GRID</span></a>
        <div class="nav-links">
            <a href="#features" class="link">Features</a>
            <a href="#how"      class="link">How it Works</a>
            <a href="#pricing"  class="link">Pricing</a>
            <a href="#faq"      class="link">FAQ</a>
            <?php if ($already_logged_in): ?>
                <a href="dashboard.php" class="btn btn-black"><i class="fas fa-th-large"></i> Dashboard</a>
            <?php else: ?>
                <a href="login.php"    class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-green"><i class="fas fa-bolt"></i> Free Trial</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ═══ HERO ══════════════════════════════════════════════ -->
<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <div style="flex:1; max-width:680px;">
                <div class="hero-badge">
                    <span class="dot"></span>
                    Now with CBSE Demo Loader — Ready in 60 seconds
                </div>
                <h1 class="hero-title" style="margin:1rem 0 1.25rem;">
                    School Timetable <br><span class="hl">Automated.</span> Conflict-Free.
                </h1>
                <p class="hero-sub">
                    TimeGrid auto-generates 100% conflict-free academic timetables for schools & colleges.
                    4 powerful viewing modes, daily substitution management, PDF export — all in one system.
                </p>
                <div class="hero-ctas" style="margin:1.75rem 0;">
                    <?php if ($already_logged_in): ?>
                        <a href="dashboard.php" class="btn btn-green btn-lg"><i class="fas fa-th-large"></i> Go to Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-green btn-lg"><i class="fas fa-bolt"></i> Start Free Trial</a>
                        <a href="https://wa.me/919431426600" class="btn btn-wa btn-lg" target="_blank"><i class="fab fa-whatsapp"></i> Book a Demo</a>
                    <?php endif; ?>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat"><strong>500+</strong><span>Schools</span></div>
                    <div class="hero-stat"><strong>0</strong><span>Conflicts</span></div>
                    <div class="hero-stat"><strong>60s</strong><span>Setup Time</span></div>
                    <div class="hero-stat"><strong>14 Days</strong><span>Free Trial</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ MARQUEE STRIP ════════════════════════════════════ -->
<div class="strip">
    <div class="strip-track">
        <?php
        $items = [
            ['bolt','AI Timetable Generation'],['calendar-alt','4 Routine View Modes'],
            ['user-clock','Daily Substitution'],['file-pdf','PDF Export'],
            ['chart-pie','Workload Analysis'],['shield-alt','100% Conflict-Free'],
            ['magic','CBSE Demo Loader'],['university','Multi-School SaaS'],
            ['lock','Secure Auth'],['print','Print-Ready'],
        ];
        // Double for seamless loop
        for ($i=0;$i<2;$i++):
        foreach ($items as [$ic,$label]):
        ?>
        <div class="strip-item">
            <i class="fas fa-<?php echo $ic; ?>"></i> <?php echo $label; ?>
            &nbsp;&nbsp;•
        </div>
        <?php endforeach; endfor; ?>
    </div>
</div>

<!-- ═══ FEATURES ════════════════════════════════════════ -->
<section class="features" id="features">
    <div class="container">
        <div class="text-center">
            <span class="sec-label">Everything you need</span>
            <h2 class="sec-title">Powerful Features,<br>Built for Indian Schools</h2>
            <p class="sec-sub">From setup to timetable generation in minutes. Everything your admin team needs.</p>
        </div>
        <div class="feat-grid">
            <?php
            $feats = [
                ['bolt',         'AI Timetable Generator',   'Greedy algorithm with backtracking. No clashes, no overlaps. Generates complete routines in seconds.'],
                ['calendar-day', 'Day-wise Routine',         'See all class periods for any selected day. TODAY indicator and lunch break rows auto-inserted.'],
                ['school',       'Class-wise Schedule',      'Full weekly schedule for any class. Select from dropdown and see period-by-period assignments.'],
                ['chalkboard-teacher','Teacher Schedule',    'Every teacher\'s full week at a glance. Perfect for planning and workload distribution.'],
                ['clock',        'Period-wise Grid',         'All classes across all days for any given period. Great for identifying free slots.'],
                ['user-clock',   'Substitution Center',      'Mark absent teachers. Only free teachers shown per period. One-click proxy assignment.'],
                ['file-pdf',     'PDF Export per Day',       'Beautiful print-ready HTML routines for every working day. Export as PDF directly from browser.'],
                ['chart-pie',    'Workload Analysis',        'Teacher-wise load breakdown, coverage %, unassigned slot detection. Instant insight reporting.'],
                ['wand-magic-sparkles','CBSE Demo Loader',  'One-click: load 12 classes, 15 CBSE subjects, 15 teachers, auto-map all — then generate!'],
                ['sliders',      '6-Step Setup Wizard',     'Guided wizard for days, periods, lunch, subject mapping, teacher assignment and generation.'],
                ['university',   'Multi-School (SaaS)',      'Each school has fully isolated data. Unlimited orgs on one server. Trial + subscription model.'],
                ['lock',         'Secure Authentication',    'Password hashing, token-based password reset, session guards, and org-level access control.'],
            ];
            foreach ($feats as [$ic,$title,$desc]):
            ?>
            <div class="feat-card">
                <div class="feat-icon"><i class="fas fa-<?php echo $ic; ?>"></i></div>
                <h3><?php echo $title; ?></h3>
                <p><?php echo $desc; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ ROUTINE VIEW MODES ═══════════════════════════════ -->
<section class="views" style="padding:4rem 0;">
    <div class="container">
        <div class="text-center" style="margin-bottom:0.5rem;">
            <span class="sec-label">Routine Viewer</span>
            <h2 class="sec-title">4 Ways to View Your Timetable</h2>
            <p class="sec-sub">Switch between views instantly. No page reloads. Always context-aware.</p>
        </div>
        <div class="view-cards">
            <div class="view-card">
                <div class="view-icon" style="background:#eff6ff;color:#3b82f6;"><i class="fas fa-calendar-day"></i></div>
                <div><h4>Day-wise</h4><p>All classes × periods for Monday, Tuesday… See TODAY highlighted automatically.</p></div>
            </div>
            <div class="view-card">
                <div class="view-icon" style="background:#f0fdf4;color:#22c55e;"><i class="fas fa-school"></i></div>
                <div><h4>Class-wise</h4><p>Select any class and see its complete week schedule in one clean table.</p></div>
            </div>
            <div class="view-card">
                <div class="view-icon" style="background:#f5f3ff;color:#8b5cf6;"><i class="fas fa-chalkboard-teacher"></i></div>
                <div><h4>Teacher-wise</h4><p>Every teacher's full weekly load — subjects, classes, free periods.</p></div>
            </div>
            <div class="view-card">
                <div class="view-icon" style="background:#fff7ed;color:#f97316;"><i class="fas fa-clock"></i></div>
                <div><h4>Period-wise</h4><p>See which class has which subject at any given period across the whole week.</p></div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ HOW IT WORKS ════════════════════════════════════ -->
<section class="how" id="how">
    <div class="container">
        <div class="text-center">
            <span class="sec-label">Simple Process</span>
            <h2 class="sec-title">From Zero to Full Timetable<br>in Under 5 Minutes</h2>
        </div>
        <div class="steps">
            <div class="step-card">
                <div class="step-num">1</div>
                <h4>Configure Week</h4>
                <p>Set working days, periods per day, lunch position, Saturday half-day. Done in 30 seconds.</p>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <h4>Add Classes & Subjects</h4>
                <p>Use the demo loader to get Classes V–X + 15 CBSE subjects with one click.</p>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <h4>Map Teachers</h4>
                <p>Auto-assign 15 demo teachers to subjects across all classes automatically.</p>
            </div>
            <div class="step-card">
                <div class="step-num">4</div>
                <h4>Generate ⚡</h4>
                <p>Hit Generate. The engine creates a 100% conflict-free timetable in seconds.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══ DEMO STRIP ════════════════════════════════════════ -->
<div class="container">
    <div class="demo-strip">
        <div style="flex:1; min-width:260px;">
            <h3>Try the Full Demo in 60 Seconds</h3>
            <p>No manual data entry. One button per step. See a complete school routine instantly.</p>
            <div class="demo-steps">
                <div class="demo-step"><span class="n">1</span> Load Classes</div>
                <div class="demo-step"><span class="n">2</span> Load Subjects</div>
                <div class="demo-step"><span class="n">3</span> Auto-Map</div>
                <div class="demo-step"><span class="n">4</span> Load Teachers</div>
                <div class="demo-step"><span class="n">5</span> Generate ⚡</div>
            </div>
        </div>
        <div style="flex-shrink:0;">
            <a href="register.php" class="btn btn-green btn-lg">
                <i class="fas fa-bolt"></i> Try It Free
            </a>
        </div>
    </div>
</div>

<!-- ═══ PRICING ══════════════════════════════════════════ -->
<section class="pricing" id="pricing">
    <div class="container text-center">
        <span class="sec-label">Simple Pricing</span>
        <h2 class="sec-title">One Plan. Everything Included.</h2>
        <p class="sec-sub">No hidden fees. No per-teacher charges. Flat annual rate for your whole school.</p>
        <div class="price-card">
            <div class="price-label">Institutional Pro</div>
            <div class="price-name">TimeGrid Annual</div>
            <div class="price-amount"><sup>₹</sup>2999<sub>/ yr</sub></div>
            <ul class="price-list">
                <li><i class="fas fa-check-circle"></i> Unlimited Classes & Teachers</li>
                <li><i class="fas fa-check-circle"></i> AI Conflict-Free Generation</li>
                <li><i class="fas fa-check-circle"></i> All 4 Routine View Modes</li>
                <li><i class="fas fa-check-circle"></i> Daily Substitution Manager</li>
                <li><i class="fas fa-check-circle"></i> PDF Export for Every Day</li>
                <li><i class="fas fa-check-circle"></i> Workload Analysis Reports</li>
                <li><i class="fas fa-check-circle"></i> CBSE Demo Data Loader</li>
                <li><i class="fas fa-check-circle"></i> Password Reset & Multi-Admin</li>
            </ul>
            <a href="register.php" class="btn btn-black btn-lg" style="width:100%;justify-content:center;">
                Start 14-Day Free Trial <i class="fas fa-arrow-right"></i>
            </a>
            <p class="trial-note">No credit card required. Cancel anytime.</p>
        </div>
    </div>
</section>

<!-- ═══ TESTIMONIALS ══════════════════════════════════════ -->
<section class="trust">
    <div class="container">
        <div class="text-center">
            <span class="sec-label">Trusted by Schools</span>
            <h2 class="sec-title">What Principals Say</h2>
        </div>
        <div class="trust-grid">
            <div class="trust-card">
                <div class="trust-stars">★★★★★</div>
                <p class="trust-text">"Jo kaam 3 din leta tha, ab 5 minute mein ho jata hai. TimeGrid ne humara timetable process completely badal diya."</p>
                <div class="trust-author">
                    <div class="trust-avatar">RK</div>
                    <div><div class="trust-name">Rajesh Kumar</div><div class="trust-role">Principal, DPS Ranchi</div></div>
                </div>
            </div>
            <div class="trust-card">
                <div class="trust-stars">★★★★★</div>
                <p class="trust-text">"No more clashes. No more Excel headaches. The substitution feature is a lifesaver during exam season and staff absences."</p>
                <div class="trust-author">
                    <div class="trust-avatar">PS</div>
                    <div><div class="trust-name">Priya Sharma</div><div class="trust-role">Academic Head, St. Xavier's</div></div>
                </div>
            </div>
            <div class="trust-card">
                <div class="trust-stars">★★★★★</div>
                <p class="trust-text">"The CBSE demo loader helped us understand the system in 60 seconds. Our IT team was impressed by how clean the code is."</p>
                <div class="trust-author">
                    <div class="trust-avatar">AM</div>
                    <div><div class="trust-name">Amit Mehta</div><div class="trust-role">IT Admin, Kendriya Vidyalaya</div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ FAQ ═══════════════════════════════════════════════ -->
<section class="faq" id="faq">
    <div class="container text-center">
        <span class="sec-label">FAQ</span>
        <h2 class="sec-title">Frequently Asked Questions</h2>
        <div class="faq-list">
            <?php
            $faqs = [
                ['Kya ek school ke liye free trial milega?', 'Haan! Register karne par aapko 14 din ka free trial milta hai. Koi credit card nahi chahiye.'],
                ['How many classes and teachers can I add?', 'Unlimited. There are no per-class or per-teacher charges. The flat annual plan covers your entire institution.'],
                ['Can multiple schools use the same system?', 'Yes — TimeGrid is a multi-tenant SaaS. Each school\'s data is fully isolated. Ideal for chains and trusts.'],
                ['Does it support Saturday half-day?', 'Yes. You can set a different period count for Saturday (e.g. 4 periods instead of 8).'],
                ['What is the CBSE Demo Loader?', 'It adds 12 standard classes, 15 CBSE subjects, and 15 teachers with auto-assignments in one click — great for trying the system.'],
                ['Can I export the timetable as PDF?', 'Yes. Every working day has a dedicated print/PDF page. Just open it in a browser and use Ctrl+P.'],
                ['Is there a setup fee or hidden cost?', 'No hidden fees. ₹2999/year, flat. All features included from day one.'],
                ['How fast does generation run?', 'Typically 2–5 seconds for a full school with 12 classes, 6 days, 8 periods. Some larger setups may take up to 30 seconds.'],
            ];
            foreach ($faqs as $i => [$q,$a]):
            ?>
            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">
                    <?php echo $q; ?> <i class="fas fa-plus"></i>
                </button>
                <div class="faq-a"><?php echo $a; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ CTA ═══════════════════════════════════════════════ -->
<div class="container">
    <div class="cta-sec">
        <h2>Ready to Automate Your School Timetable?</h2>
        <p>Join 500+ schools across India. Setup in under 5 minutes. No Excel. No Clashes. No Stress.</p>
        <div class="cta-btns">
            <a href="register.php" class="btn btn-green btn-lg"><i class="fas fa-bolt"></i> Start Free Trial</a>
            <a href="https://wa.me/919431426600" class="btn btn-wa btn-lg" target="_blank"><i class="fab fa-whatsapp"></i> +91 9431426600</a>
        </div>
    </div>
</div>

<!-- ═══ FOOTER ════════════════════════════════════════════ -->
<footer>
    <div class="container">
        <div class="footer-inner">
            <div class="footer-logo"><i class="fas fa-calendar-alt" style="color:var(--green);"></i> TIME<span>GRID</span></div>
            <div class="footer-links">
                <a href="#features">Features</a>
                <a href="#how">How it Works</a>
                <a href="#pricing">Pricing</a>
                <a href="login.php">Login</a>
                <a href="register.php">Free Trial</a>
            </div>
        </div>
        <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border);display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
            <p class="footer-copy">© <?php echo date('Y'); ?> TimeGrid by OfferPlant Technologies Pvt. Ltd.</p>
            <p class="footer-copy">📞 +91 9431426600 &nbsp;|&nbsp; <a href="https://offerplant.com" style="color:var(--grey);">offerplant.com</a></p>
        </div>
    </div>
</footer>

<script>
// Navbar scroll effect
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

// FAQ toggle
function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const isOpen = btn.classList.contains('open');
    // Close all
    document.querySelectorAll('.faq-q.open').forEach(b => {
        b.classList.remove('open');
        b.nextElementSibling.classList.remove('open');
    });
    if (!isOpen) {
        btn.classList.add('open');
        answer.classList.add('open');
    }
}

// Scroll reveal
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.style.opacity = '1';
            e.target.style.transform = 'translateY(0)';
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.feat-card, .step-card, .view-card, .trust-card, .faq-item').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    observer.observe(el);
});
</script>
</body>
</html>
