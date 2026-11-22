<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . getRoleDashboardUrl());
    exit;
}

$errors = [];
$registration = ['full_name' => '', 'registration_number' => '', 'email' => ''];
$registered = isset($_GET['registered']) && $_GET['registered'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session token expired. Please refresh and try again.';
    }

    foreach ($registration as $field => $default) {
        $registration[$field] = trim((string)($_POST[$field] ?? $default));
    }
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');

    if ($registration['full_name'] === '' || mb_strlen($registration['full_name'], 'UTF-8') > 150) {
        $errors[] = 'Enter your full name (maximum 150 characters).';
    }
    if (!preg_match('~^[A-Za-z0-9._/-]{2,50}$~', $registration['registration_number'])) {
        $errors[] = 'Enter a valid registration number.';
    }
    if (!filter_var($registration['email'], FILTER_VALIDATE_EMAIL) || strlen($registration['email']) > 150) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'The password confirmation does not match.';
    }

    if (!$errors) {
        $conn = db();
        $conn->begin_transaction();
        try {
            $username = $registration['registration_number'];
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                throw new RuntimeException('Unable to secure the password.');
            }

            $userStmt = $conn->prepare(
                "INSERT INTO users (username, email, password_hash, role, status, approval_status)
                 VALUES (?, ?, ?, 'student', 'active', 'pending')"
            );
            if (!$userStmt) {
                throw new RuntimeException('Unable to prepare account registration.');
            }
            $userStmt->bind_param('sss', $username, $registration['email'], $passwordHash);
            if (!$userStmt->execute()) {
                $errorCode = $userStmt->errno;
                $userStmt->close();
                if ($errorCode === 1062) {
                    throw new DomainException('That registration number or email address is already registered.');
                }
                throw new RuntimeException('Unable to save the student account.');
            }
            $userId = (int)$userStmt->insert_id;
            $userStmt->close();

            $profileStmt = $conn->prepare(
                'INSERT INTO student_profiles (user_id, full_name, student_number) VALUES (?, ?, ?)'
            );
            if (!$profileStmt) {
                throw new RuntimeException('Unable to prepare the student profile.');
            }
            $profileStmt->bind_param('iss', $userId, $registration['full_name'], $registration['registration_number']);
            if (!$profileStmt->execute()) {
                $errorCode = $profileStmt->errno;
                $profileStmt->close();
                if ($errorCode === 1062) {
                    throw new DomainException('That registration number is already registered.');
                }
                throw new RuntimeException('Unable to save the student profile.');
            }
            $profileStmt->close();

            if (!$conn->commit()) {
                throw new RuntimeException('Unable to complete student registration.');
            }
            header('Location: ' . projectUrl('register.php?registered=1'));
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            if ($e instanceof DomainException) {
                $errors[] = $e->getMessage();
            } elseif ($e instanceof PDOException && $e->getCode() === '23505') {
                $errors[] = 'That registration number or email address is already registered.';
            } else {
                error_log('CourtIQ student registration failed: ' . $e->getMessage());
                $errors[] = 'Registration could not be completed. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Registration | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/login.css') ?>">
</head>
<body class="login-body">
    <main class="login-form-panel">
        <div class="login-form-container">
            <header class="login-form-header">
                <a class="brand" href="<?= projectUrl('index.php') ?>"><span class="brand-text">Court<span>IQ</span></span></a>
                <span class="form-tag">Student Portal</span>
                <h1 class="form-title">Create a student account</h1>
                <p class="form-subtitle">Your account will be reviewed by an administrator before access is enabled.</p>
            </header>

            <?php if ($registered): ?>
                <div class="alert alert-success" role="status">
                    Registration received. Your account is awaiting Administrator approval. You can sign in after it is approved.
                </div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert">
                    <div class="alert-text-list"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= projectUrl('register.php') ?>" class="login-form">
                <?= csrfField() ?>
                <div class="form-field">
                    <label class="field-label" for="full_name">Full name</label>
                    <input class="form-input" id="full_name" name="full_name" required maxlength="150" autocomplete="name" value="<?= e($registration['full_name']) ?>">
                </div>
                <div class="form-field">
                    <label class="field-label" for="registration_number">Registration number</label>
                    <input class="form-input" id="registration_number" name="registration_number" required maxlength="50" autocomplete="username" value="<?= e($registration['registration_number']) ?>">
                </div>
                <div class="form-field">
                    <label class="field-label" for="email">Email address</label>
                    <input class="form-input" id="email" name="email" type="email" required maxlength="150" autocomplete="email" value="<?= e($registration['email']) ?>">
                </div>
                <div class="form-field">
                    <label class="field-label" for="password">Password</label>
                    <input class="form-input" id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
                </div>
                <div class="form-field">
                    <label class="field-label" for="password_confirmation">Confirm password</label>
                    <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit">Register</button>
                <p class="form-subtitle"><a href="<?= projectUrl('login.php') ?>">Already have an account? Sign in</a></p>
            </form>
        </div>
    </main>
</body>
</html>
