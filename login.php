<?php
// Login screen. The form submit is the event. PHP checks the hashed password and starts the session.

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('home.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $errors[] = 'The form expired. Please try again.';
    }

    $emailError = validate_email($email);
    if ($emailError) {
        $errors[] = $emailError;
    }
    if ($password === '') {
        $errors[] = 'Please enter your password';
    }

    if (login_is_locked()) {
        $wait = login_lock_remaining();
        $errors[] = 'Too many failed sign-ins. Try again in ' . $wait . ' seconds. This lock is stored in the PHP session.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare('SELECT id, username, email, password, role FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                login_record_failure();
                $left = max(0, 5 - (int) ($_SESSION['login_fails'] ?? 0));
                $errors[] = $left > 0
                    ? 'Email or password is incorrect. ' . $left . ' attempt(s) left before a short lock.'
                    : 'Email or password is incorrect. Sign-in is locked for 5 minutes.';
            } else {
                login_clear_failures();
                $update = db()->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
                $update->execute([':id' => $user['id']]);
                login_user($user);
                redirect('home.php');
            }
        } catch (Throwable $exception) {
            $errors[] = 'The database is not available. Start MySQL in XAMPP and try again.';
        }
    }
}

$pageTitle = 'Sign in';
$showAppBar = false;
$pageScripts = ['assets/js/auth.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="auth-main">
    <svg class="heart-icon heart-icon-blue" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 21s-6.7-4.4-9.3-8.2C.8 10.2 1.1 6.8 3.6 5.2 5.6 3.9 8.2 4.3 10 6.1L12 8.2l2-2.1c1.8-1.8 4.4-2.2 6.4-.9 2.5 1.6 2.8 5 1 7.6C18.7 16.6 12 21 12 21z"/>
    </svg>
    <h1>Welcome back</h1>
    <p class="muted center">Your virtual identity starts with this sign-in.</p>

    <?php if ($errors): ?>
        <div class="form-errors" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('login.php')) ?>" id="auth-form" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>
            Email
            <input type="email" name="email" id="email" value="<?= e($email) ?>" autocomplete="email" required>
        </label>
        <label>
            Password
            <span class="password-field">
                <input type="password" name="password" id="password" autocomplete="current-password" required>
                <button type="button" class="toggle-password" data-target="password" aria-label="Show password">Show</button>
            </span>
        </label>
        <button type="submit" class="btn btn-primary">Sign In</button>
    </form>
    <a class="text-link" href="<?= e(url('register.php')) ?>">Don't have an account? Sign Up</a>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
