<?php
// Register screen. A new account is always a normal user. The role is not taken from the form.

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('home.php');
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!csrf_valid()) {
        $errors[] = 'The form expired. Please try again.';
    }

    foreach ([
        validate_username($username),
        validate_email($email),
        validate_password($password),
    ] as $error) {
        if ($error) {
            $errors[] = $error;
        }
    }

    if ($confirm === '') {
        $errors[] = 'Please confirm your password';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match';
    }

    if (!$errors) {
        try {
            $pdo = db();
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username');
            $check->execute([':email' => $email, ':username' => $username]);

            if ($check->fetch()) {
                $errors[] = 'An account with that email or username already exists.';
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)'
                );
                $insert->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':role' => 'user',
                ]);

                login_user([
                    'id' => (int) $pdo->lastInsertId(),
                    'username' => $username,
                    'email' => $email,
                    'role' => 'user',
                ]);
                redirect('home.php');
            }
        } catch (Throwable $exception) {
            $errors[] = 'The database is not available. Start MySQL in XAMPP and try again.';
        }
    }
}

$pageTitle = 'Create Account';
$barTitle = 'Create Account';
$backHref = 'login.php';
$pageScripts = ['assets/js/auth.js'];
require __DIR__ . '/includes/header.php';
?>
<main class="auth-main">
    <svg class="heart-icon heart-icon-blue" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="none" stroke="currentColor" stroke-width="1.7" d="M12 20s-6.2-4.1-8.6-7.6C1.4 10 1.7 7 3.9 5.6 5.6 4.5 8 4.8 9.6 6.4L12 8.8l2.4-2.4c1.6-1.6 4-1.9 5.7-.8 2.2 1.4 2.5 4.4.5 6.8C18.2 15.9 12 20 12 20z"/>
    </svg>
    <h1>Create Account</h1>
    <p class="muted center">Join Heart Game and start playing!</p>

    <?php if ($errors): ?>
        <div class="form-errors" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('register.php')) ?>" id="auth-form" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>
            Username
            <input type="text" name="username" id="username" value="<?= e($username) ?>" autocomplete="username" required>
        </label>
        <label>
            Email
            <input type="email" name="email" id="email" value="<?= e($email) ?>" autocomplete="email" required>
        </label>
        <label>
            Password
            <span class="password-field">
                <input type="password" name="password" id="password" autocomplete="new-password" required>
                <button type="button" class="toggle-password" data-target="password" aria-label="Show password">Show</button>
            </span>
        </label>
        <label>
            Confirm Password
            <span class="password-field">
                <input type="password" name="confirm_password" id="confirm_password" autocomplete="new-password" required>
                <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">Show</button>
            </span>
        </label>
        <button type="submit" class="btn btn-primary">Create Account</button>
    </form>
    <a class="text-link" href="<?= e(url('login.php')) ?>">Already have an account? Sign In</a>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
