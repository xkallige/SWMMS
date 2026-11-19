<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$reportCount = (int) db()->query('SELECT COUNT(*) FROM waste_reports')->fetchColumn();
$resolvedCount = (int) db()->query("SELECT COUNT(*) FROM waste_reports WHERE status = 'resolved'")->fetchColumn();
$facilityCount = (int) db()->query('SELECT COUNT(*) FROM facilities')->fetchColumn();
$activeUserCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$facilities = db()->query('SELECT name, facility_type, address, operational_status FROM facilities ORDER BY created_at DESC LIMIT 4')->fetchAll();
$recentResolved = db()->query(
    "SELECT wr.id, wc.name AS category, wr.updated_at
     FROM waste_reports wr INNER JOIN waste_categories wc ON wc.id = wr.category_id
     WHERE wr.status = 'resolved' ORDER BY wr.updated_at DESC LIMIT 3"
)->fetchAll();
$authMode = match ($_GET['auth'] ?? '') {
    'login', 'register', 'forgot' => (string) $_GET['auth'],
    default => null,
};
$authError = flash('auth_error');
$authSuccess = flash('auth_success');
$pageTitle = 'Cleaner streets, smarter response';
$pageClass = 'home-page';
require __DIR__ . '/includes/header.php';
?>
<section class="home-hero">
    <div class="container">
        <div class="hero-layout">
            <div class="hero-image-panel">
                <div class="hero-copy-panel">
                    <div class="eyebrow eyebrow-light"><span class="eyebrow-dot"></span> Digital infrastructure for cleaner communities</div>
                    <h1>Make every street<br><em>count.</em></h1>
                    <p class="hero-copy">SmartWaste GIS turns everyday waste concerns into visible, trackable action — connecting residents, field officers, and city teams in one clear workflow.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a class="btn hero-action hero-action-primary btn-lg px-4" href="<?= APP_URL ?>/?auth=register">Report a problem <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="hero-note mt-4"><span class="check-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6"/></svg></span> Location-based reporting · transparent progress · coordinated response</div>
                </div>
            </div>
            <div class="hero-visual-panel">
                <div class="hero-visual">
                    <div class="visual-orbit orbit-one"></div><div class="visual-orbit orbit-two"></div>
                    <div class="visual-card visual-card-main"><span class="visual-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></span><div><strong>Waste hotspot</strong><small>New report · Port Harcourt</small></div><span class="status-pulse"></span></div>
                    <div class="visual-card visual-card-top"><span class="mini-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6"/></svg></span><div><strong>Resolved</strong><small>Collection completed</small></div></div>
                    <div class="visual-card visual-card-bottom"><span class="mini-icon blue" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 19 19 5M9 5h10v10"/></svg></span><div><strong>Live operations</strong><small>Map data updated</small></div></div>
                    <div class="visual-center"><span>SW</span><small>Cleaner<br>communities</small></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="quick-actions"><div class="container"><div class="quick-actions-grid">
    <a href="<?= APP_URL ?>/?auth=register"><span class="quick-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></span><span><strong>Report waste</strong><small>Pin a problem on the map</small></span><b>→</b></a>
    <a href="<?= APP_URL ?>/?auth=login"><span class="quick-icon gold" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 19 19 5M9 5h10v10"/><path d="M5 5v5"/></svg></span><span><strong>Track a report</strong><small>Follow work to resolution</small></span><b>→</b></a>
    <a href="<?= APP_URL ?>/?auth=register"><span class="quick-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 11v6M13 14h6"/></svg></span><span><strong>Join your community</strong><small>Help improve local data</small></span><b>→</b></a>
</div></div></section>

<section class="stats-strip"><div class="container"><div class="stats-panel"><div class="row g-0">
    <div class="col-6 col-lg-3 stat-item"><strong><?= number_format($reportCount) ?></strong><span>Reports logged</span></div>
    <div class="col-6 col-lg-3 stat-item"><strong><?= number_format($resolvedCount) ?></strong><span>Problems resolved</span></div>
    <div class="col-6 col-lg-3 stat-item"><strong><?= number_format($facilityCount) ?></strong><span>Mapped facilities</span></div>
    <div class="col-6 col-lg-3 stat-item"><strong><?= number_format($activeUserCount) ?></strong><span>Active participants</span></div>
</div></div></div></section>

<section class="section-space" id="services"><div class="container">
    <div class="section-heading row align-items-end g-3"><div class="col-lg-7"><div class="eyebrow">One connected system</div><h2>From first report to final collection.</h2></div><div class="col-lg-4 ms-auto"><p>Good waste management starts with good information. SmartWaste GIS gives every concern a place, a status, and a next step.</p></div></div>
    <div class="process-grid mt-5">
        <div class="process-card"><div class="process-card-top"><span class="process-number">01</span><span class="process-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/><path d="M4 20h16"/></svg></span></div><div class="process-content"><h3>Capture what matters</h3><p>Residents submit a clear description, photo, and exact location from any device.</p><a href="<?= APP_URL ?>/?auth=register">Start reporting <span>→</span></a></div></div>
        <div class="process-connector" aria-hidden="true"><span></span></div>
        <div class="process-card"><div class="process-card-top"><span class="process-number">02</span><span class="process-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v11H4z"/><path d="M8 20h8M12 16v4M8 9h8M8 12h5"/></svg></span></div><div class="process-content"><h3>Coordinate the response</h3><p>Administrators review reports, assign officers, and keep every status change visible.</p><a href="<?= APP_URL ?>/login.php">See the workflow <span>→</span></a></div></div>
        <div class="process-connector" aria-hidden="true"><span></span></div>
        <div class="process-card"><div class="process-card-top"><span class="process-number">03</span><span class="process-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5"/><path d="M12 3a9 9 0 1 0 9 9"/></svg></span></div><div class="process-content"><h3>Close the loop</h3><p>Field teams record collection work so communities can see progress, not just promises.</p><a href="<?= APP_URL ?>/?auth=login">Track progress <span>→</span></a></div></div>
    </div>
</div></section>

<section class="section-space"><div class="container">
    <div class="section-heading row align-items-end g-3"><div class="col-lg-7"><div class="eyebrow">Our core services</div><h2>Cleaner systems.<br><em>Better outcomes.</em></h2></div><div class="col-lg-4 ms-auto"><p>From the first community report to the last collection record, every service is designed to make action easier to organise and easier to trust.</p></div></div>
    <div class="service-list mt-5">
        <div class="service-row"><span class="service-index">01</span><div class="service-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/><path d="M12 2v3M5 9H2M22 9h-3"/></svg></div><div><h3>Location-based reporting</h3><p>Capture illegal dumping, overflowing bins, blocked drainage, and other waste concerns with a precise location.</p></div><a href="<?= APP_URL ?>/register.php">Report a problem <span>→</span></a></div>
        <div class="service-row"><span class="service-index">02</span><div class="service-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v11H4z"/><path d="M8 20h8M12 16v4M8 9h8M8 12h5"/><path d="m16 12 2 2 3-3"/></svg></div><div><h3>Coordinated field response</h3><p>Route verified reports to officers, assign tasks, and keep residents informed as work progresses.</p></div><a href="<?= APP_URL ?>/?auth=login">See operations <span>→</span></a></div>
        <div class="service-row"><span class="service-index">04</span><div class="service-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M8 10h8M8 14h5M8 18l1.5 1.5L13 16"/></svg></div><div><h3>Collection accountability</h3><p>Record quantities, dates, remarks, and completion status so outcomes are measurable over time.</p></div><a href="<?= APP_URL ?>/?auth=login">View the workflow <span>→</span></a></div>
    </div>
</div></section>


<section class="section-space" id="updates"><div class="container"><div class="section-heading row align-items-end g-3"><div class="col-lg-7"><div class="eyebrow">Public updates</div><h2>Progress you can<br><em>see and share.</em></h2></div><div class="col-lg-4 ms-auto"><p>Resolved reports create a record of what communities and field teams are achieving together.</p></div></div>
    <div class="row g-4 mt-4"><?php foreach ($recentResolved as $resolved): ?><div class="col-md-4"><div class="notice-card"><span class="notice-label">Resolution update</span><small><?= e(date('j F Y', strtotime($resolved['updated_at']))) ?></small><h3>Report #<?= e($resolved['id']) ?> resolved</h3><p><?= e($resolved['category']) ?> concern was marked resolved by the operations team.</p></div></div><?php endforeach; ?><?php if (!$recentResolved): ?><div class="col-12"><div class="empty-network"><strong>Updates will appear here as reports are resolved.</strong><p>Submit a report to help create the first public progress record.</p></div></div><?php endif; ?></div>
</div></section>

<section class="faq-section"><div class="container"><div class="faq-layout"><div class="faq-intro"><div class="eyebrow">Need to know</div><h2>Frequently asked<br><em>questions.</em></h2><p>Find straightforward answers about reporting concerns, tracking progress, and how SmartWaste GIS supports cleaner communities.</p><a href="<?= APP_URL ?>/register.php" class="faq-contact">Join the network <span>→</span></a></div><div class="accordion clean-accordion" id="faq"><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#faqOne">What can I report?</button></h3><div id="faqOne" class="accordion-collapse collapse show" data-bs-parent="#faq"><div class="accordion-body">You can report illegal dumping, overflowing bins, uncollected household waste, plastic accumulation, blocked drainage caused by waste, commercial waste, and other waste concerns.</div></div></div><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faqTwo">How does location selection work?</button></h3><div id="faqTwo" class="accordion-collapse collapse" data-bs-parent="#faq"><div class="accordion-body">Choose a point directly on the map or allow your browser to use your current location. The selected coordinates are stored with your report for verification and response planning.</div></div></div><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faqThree">Who manages a submitted report?</button></h3><div id="faqThree" class="accordion-collapse collapse" data-bs-parent="#faq"><div class="accordion-body">Administrators review incoming reports and assign them to active waste management officers. Every status change is recorded in the report history.</div></div></div><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faqFour">Can I follow progress after submitting?</button></h3><div id="faqFour" class="accordion-collapse collapse" data-bs-parent="#faq"><div class="accordion-body">Yes. Residents can view their reports, see the current status, receive notifications, and follow the workflow through to resolution.</div></div></div></div></div></div></section>

<section class="section-space section-tint"><div class="container"><div class="row align-items-center g-5">
    <div class="col-lg-5"><div class="eyebrow">Designed for real operations</div><h2>Less noise.<br><em>More signal.</em></h2><p class="mt-3">The platform brings scattered reports, field updates, facilities, and community context into one shared source of truth.</p><a class="btn btn-outline-dark mt-2" href="<?= APP_URL ?>/?auth=login">Open your portal <span class="ms-2">→</span></a></div>
    <div class="col-lg-7"><div class="principles-grid"><div><span>01</span><h3>Location-first</h3><p>Every report is anchored to a real place for better prioritisation.</p></div><div><span>02</span><h3>Accountable</h3><p>History, notifications, and roles keep responsibilities clear.</p></div><div><span>03</span><h3>Built to grow</h3><p>Structured data is ready for future GIS analysis and reporting.</p></div><div><span>04</span><h3>Human-centred</h3><p>Simple flows for residents, officers, and coordinators.</p></div></div></div>
</div></div></section>

<section class="cta-band"><div class="container"><div class="row align-items-center g-4"><div class="col-lg-8"><div class="eyebrow eyebrow-light">Your street is part of the solution</div><h2>See something that needs attention?</h2><p>Give your community a clearer path from concern to action.</p></div><div class="col-lg-4 text-lg-end"><a class="btn btn-light btn-lg px-4" href="<?= APP_URL ?>/?auth=register">Create your account <span aria-hidden="true">→</span></a></div></div></div></section>
<?php if ($authMode): ?>
<div class="portal-overlay" role="dialog" aria-modal="true" aria-labelledby="portal-title">
    <a class="portal-overlay-dismiss" href="<?= APP_URL ?>/" aria-label="Close sign-in window">×</a>
    <div class="portal-card <?= $authMode === 'register' ? 'portal-card-register' : '' ?>">
        <a class="portal-card-close" href="<?= APP_URL ?>/" aria-label="Cancel <?= $authMode === 'register' ? 'registration' : 'login' ?>">×</a>
        <div class="auth-card-brand"><span class="brand-mark">S</span></div>
        <div class="eyebrow">Resident portal</div>
        <?php if ($authMode === 'forgot'): ?>
            <h2 id="portal-title">Forgot your password?</h2>
            <p class="auth-subtitle">Enter your email and we will send a secure password reset link if an account exists.</p>
            <?php if ($authError): ?><div class="alert alert-danger"><?= e($authError) ?></div><?php endif; ?>
            <?php if ($authSuccess): ?>
                <div class="alert alert-success"><?= e($authSuccess) ?></div>
                <a class="auth-secondary-action" href="<?= APP_URL ?>/?auth=login">Return to sign in <span aria-hidden="true">→</span></a>
            <?php else: ?>
                <form method="post" action="<?= APP_URL ?>/forgot-password.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <label class="form-label" for="portal-forgot-email">Email address</label>
                    <input class="form-control mb-3" id="portal-forgot-email" name="email" type="email" autocomplete="email" required>
                    <button class="btn auth-submit w-100" type="submit">Send reset link <span>→</span></button>
                </form>
                <div class="auth-divider"><span>Remembered your password?</span></div>
                <a class="auth-secondary-action" href="<?= APP_URL ?>/?auth=login">Sign in securely <span aria-hidden="true">→</span></a>
            <?php endif; ?>
        <?php elseif ($authMode === 'login'): ?>
            <h2 id="portal-title">Welcome back</h2>
            <p class="auth-subtitle">Sign in to view reports, track progress, and manage your waste concerns.</p>
            <form method="post" action="<?= APP_URL ?>/login.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="form-label" for="portal-email">Email address</label>
                <input class="form-control mb-3" id="portal-email" name="email" type="email" autocomplete="email" required>
                <label class="form-label" for="portal-password">Password</label>
                <input class="form-control mb-3" id="portal-password" name="password" type="password" autocomplete="current-password" required>
                <div class="auth-form-options"><label><input type="checkbox" name="remember"> Remember me</label><a href="<?= APP_URL ?>/?auth=forgot">Forgot password?</a></div>
                <button class="btn auth-submit w-100" type="submit">Sign in securely <span>→</span></button>
            </form>
            <div class="auth-divider"><span>New to online services?</span></div>
            <a class="auth-secondary-action" href="<?= APP_URL ?>/?auth=register">Register your resident account <span aria-hidden="true">→</span></a>
        <?php else: ?>
            <h2 id="portal-title">Create your account</h2>
            <p class="auth-subtitle">Register to report local waste problems, add precise locations, and follow progress.</p>
            <form method="post" action="<?= APP_URL ?>/register.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="form-label" for="portal-name">Full name</label>
                <input class="form-control mb-3" id="portal-name" name="full_name" autocomplete="name" required>
                <label class="form-label" for="portal-register-email">Email address</label>
                <input class="form-control mb-3" id="portal-register-email" name="email" type="email" autocomplete="email" required>
                <label class="form-label" for="portal-register-password">Password</label>
                <input class="form-control mb-3" id="portal-register-password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                <label class="form-label" for="portal-confirm-password">Confirm password</label>
                <input class="form-control mb-3" id="portal-confirm-password" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                <button class="btn auth-submit w-100" type="submit">Create account <span>→</span></button>
            </form>
            <div class="auth-divider"><span>Already registered?</span></div>
            <a class="auth-secondary-action" href="<?= APP_URL ?>/?auth=login">Sign in securely <span aria-hidden="true">→</span></a>
        <?php endif; ?>
        <p class="auth-security">Protected by secure session authentication</p>
    </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
