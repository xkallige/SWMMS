<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$registration = $_SESSION['registration_complete'] ?? null;
if (!is_array($registration)) redirect(APP_URL . '/register.php');
$selectedRole = in_array($_POST['role'] ?? '', ['resident', 'officer', 'admin'], true) ? (string) $_POST['role'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Your session token is invalid. Please try again.');
    } elseif (!in_array($_POST['role'] ?? '', ['resident', 'officer', 'admin'], true)) {
        flash('error', 'Select a portal to continue.');
    } elseif (($_POST['role'] ?? '') === 'resident') {
        unset($_SESSION['registration_complete']);
        flash('success', 'Registration successful. Sign in to open your resident dashboard.');
        redirect(APP_URL . '/login.php');
    } else {
        flash('error', 'That portal is available through a managed account. Select Resident to continue, or ask an administrator to provision your staff account.');
    }
}

$pageTitle = 'Choose your portal';
$bodyClass = 'auth-page role-selection-page';
$pageClass = 'auth-shell';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout role-selection-layout"><div class="auth-form-wrap"><div class="auth-form auth-card auth-card-register role-selection">
    <a class="auth-close" href="<?= APP_URL ?>/register.php" aria-label="Return to registration">×</a>
    <div class="eyebrow">Registration complete</div>
    <h2>How will you use SWMMS?</h2>
    <p class="role-selection-intro">Choose the portal that matches how you help create cleaner communities.</p>
    <?php if ($message = flash('error')): ?><div class="alert alert-danger"><?= e($message) ?></div><?php endif; ?>
    <form method="post" class="role-selection-form <?= $selectedRole ? 'has-selection' : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="role" id="selected-role" value="<?= e($selectedRole) ?>">
        <button class="role-choice <?= $selectedRole === 'resident' ? 'is-selected' : '' ?>" type="button" data-role="resident" aria-pressed="<?= $selectedRole === 'resident' ? 'true' : 'false' ?>">
            <span class="role-choice-icon resident"><svg viewBox="0 0 24 24" fill="none"><path d="M4 10.5 12 4l8 6.5v8.5H4v-8.5Z"/><path d="M9 19v-5h6v5"/></svg></span>
            <span><strong>I'm a Resident</strong><small>Report waste concerns and follow progress in your community.</small></span><b>→</b>
        </button>
        <button class="role-choice role-choice-locked <?= $selectedRole === 'officer' ? 'is-selected' : '' ?>" type="button" data-role="officer" aria-pressed="<?= $selectedRole === 'officer' ? 'true' : 'false' ?>">
            <span class="role-choice-icon officer"><svg viewBox="0 0 24 24" fill="none"><path d="M5 5h14v14H5z"/><path d="M8 9h8M8 13h5M8 17h3"/></svg></span>
            <span><strong>I'm an Officer</strong><small>Coordinate field work, collections, and community response.</small></span><span class="role-lock">Managed access</span>
        </button>
        <button class="role-choice role-choice-locked <?= $selectedRole === 'admin' ? 'is-selected' : '' ?>" type="button" data-role="admin" aria-pressed="<?= $selectedRole === 'admin' ? 'true' : 'false' ?>">
            <span class="role-choice-icon admin"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3 19 6v5c0 4.5-2.8 7.8-7 10-4.2-2.2-7-5.5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
            <span><strong>I'm an Administrator</strong><small>Manage users, reports, facilities, and platform operations.</small></span><span class="role-lock">Managed access</span>
        </button>
        <p class="role-selection-note" id="role-selection-note">Select a portal to continue.</p>
        <button class="btn auth-submit role-continue" type="submit">Continue <span>→</span></button>
    </form>
    <p class="role-selection-login">Already have an account? <a href="<?= APP_URL ?>/login.php">Sign in</a></p>
</div></div></div>
<script>
const selectedRole = document.getElementById('selected-role');
const roleForm = document.querySelector('.role-selection-form');
const roleNote = document.getElementById('role-selection-note');
const roleMessages = {
    resident: 'Resident access is available through public registration.',
    officer: 'Officer access is managed because field accounts are created and approved by an administrator.',
    admin: 'Administrator access is managed because platform accounts require secure provisioning.'
};
document.querySelectorAll('[data-role]').forEach((choice) => {
    choice.addEventListener('click', () => {
        selectedRole.value = choice.dataset.role;
        roleForm.classList.add('has-selection');
        roleNote.textContent = roleMessages[selectedRole.value];
        document.querySelectorAll('[data-role]').forEach((item) => {
            const selected = item === choice;
            item.classList.toggle('is-selected', selected);
            item.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
    });
});
if (selectedRole.value) roleNote.textContent = roleMessages[selectedRole.value];
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
