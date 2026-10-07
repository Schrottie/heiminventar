<?php
require_once __DIR__ . '/cfg/db.php';

// Settings & Theme laden
$settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$currentTheme = $settings['theme'] ?? 'light';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Falls der Nutzer bereits eingeloggt ist, direkt zum Dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Prüfung auf aktiven Account
            if ((int)($user['is_active'] ?? 1) !== 1) {
                $error = 'Dieser Account wurde deaktiviert. Bitte wende dich an einen Administrator.';
            } else {
                // Login erfolgreich
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'Ungültiger Benutzername oder Passwort!';
        }
    } else {
        $error = 'Bitte fülle alle Felder aus.';
    }
}
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="<?= $currentTheme === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung - Inventarsystem</title>
    
    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top right, rgba(220, 53, 69, 0.08), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(13, 110, 253, 0.05), transparent 40%);
        }
        
        .login-card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.12);
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .login-header {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
            padding: 2.5rem 1.5rem 2rem 1.5rem;
            color: #ffffff;
            text-align: center;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: #dc3545;
        }

        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
            border: none;
            padding: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.35);
        }

        .input-group-text-toggle {
            cursor: pointer;
            border-top-right-radius: 0.75rem !important;
            border-bottom-right-radius: 0.75rem !important;
        }
    </style>
</head>
<body class="<?= ($currentTheme === 'dark') ? 'theme-dark bg-dark' : 'bg-light' ?>">

<main class="w-100 px-3" style="max-width: 420px;">
    
    <div class="card login-card">
        
        <!-- Header mit Branding -->
        <div class="login-header">
            <div class="brand-badge">
                <i class="fa-solid fa-boxes-stacked fa-2x"></i>
            </div>
            <h4 class="fw-bold mb-1">WAS IST <span class="fw-light">WO?</span></h4>
            <p class="small mb-0 text-white-50">Bitte melde dich an, um fortzufahren</p>
        </div>

        <!-- Card Body -->
        <div class="card-body p-4 p-sm-5">
            
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center rounded-3 mb-4 shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2 fs-5 flex-shrink-0"></i>
                    <div class="small fw-semibold"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">
                
                <!-- Benutzername -->
                <div class="form-floating mb-3">
                    <input type="text" class="form-control rounded-3" id="username" name="username" placeholder="Benutzername" required autofocus>
                    <label for="username"><i class="fa-solid fa-user me-2"></i>Benutzername</label>
                </div>

                <!-- Passwort mit Show/Hide Toggle -->
                <div class="position-relative mb-4">
                    <div class="form-floating">
                        <input type="password" class="form-control rounded-3 pe-5" id="password" name="password" placeholder="Passwort" required>
                        <label for="password"><i class="fa-solid fa-key me-2"></i>Passwort</label>
                    </div>
                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-secondary text-decoration-none pe-3 z-3" id="togglePassword">
                        <i class="fa-regular fa-eye" id="toggleIcon"></i>
                    </button>
                </div>

                <!-- Submit Button -->
                <button class="btn btn-danger btn-login w-100 text-white mb-2" type="submit">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Anmelden
                </button>

            </form>

        </div>

        <!-- Card Footer -->
        <div class="card-footer bg-body-tertiary text-center py-3 border-0">
            <small class="text-muted"><i class="fa-solid fa-shield-halved me-1"></i> Gesicherter Systemzugang</small>
        </div>

    </div>

</main>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Passwort Sichtbarkeit umschalten -->
<script>
document.getElementById('togglePassword')?.addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

</body>
</html>
