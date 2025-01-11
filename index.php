<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true); // Récupération des données JSON
    $email = $input['email'] ?? null;
    $password = $input['password'] ?? null;
    $remember = $input['remember'] ?? false;

    try {
        // Vérification de l'utilisateur par email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Enregistrer l'utilisateur dans la session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'role' => $user['role']
            ];

            // Si "Se rappeler de moi" est coché, créer un cookie avec un token
            if ($remember) {
                $token = bin2hex(random_bytes(16));
                setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);

                $stmt = $pdo->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                $stmt->execute([':token' => $token, ':id' => $user['id']]);
            }

            // Réponse JSON
            echo json_encode([
                'success' => true,
                'role' => $user['role']
            ]);
            exit;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect.'
            ]);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la connexion : ' . $e->getMessage()
        ]);
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · Call Center Nathalie Gift</title>
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-signin {
            max-width: 320px;
            padding: 15px;
            margin: auto;
        }
        .form-control {
            font-size: 0.9rem;
        }
        @media (min-width: 768px) {
            .form-signin {
                max-width: 400px;
            }
        }
    </style>
</head>
<body class="text-center">
<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Call Center Nathalie Gift🎁🎀🎉🎊🛍️</a>
    </div>
</nav>
<main class="form-signin mt-5">
    <form id="login-form">
        <img class="mb-4" src="gift.png" alt="" width="300" height="300">
        <h1 class="h3 mb-3 fw-normal">Veuillez vous connecter</h1>
        <div id="error-message" class="alert alert-danger d-none"></div>
        <!-- Champ Email -->
        <div class="form-floating mb-3">
            <input type="email" name="email" class="form-control" id="floatingInput" placeholder="name@example.com" required>
            <label for="floatingInput">Adresse Email</label>
        </div>
        <!-- Mot de passe -->
        <div class="form-floating mb-3 position-relative">
            <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Mot de passe" required>
            <label for="floatingPassword">Mot de passe</label>
            <span class="position-absolute top-50 end-0 translate-middle-y" style="cursor: pointer; border: none; background-color: transparent;" id="eye-icon">
                <i class="bi bi-eye" id="eye-icon-toggle"></i>
            </span>
        </div>
        <!-- Se rappeler de moi -->
        <div class="checkbox mb-3">
            <label>
                <input type="checkbox" id="remember-me"> Se rappeler de moi
            </label>
        </div>
        <button class="w-100 btn btn-lg btn-primary" type="submit">Connexion</button>
        <p class="mt-5 mb-3 text-muted">&copy; 2024</p>
    </form>
    <script>
        // Afficher/Masquer le mot de passe
        const passwordField = document.getElementById('floatingPassword');
        const eyeIcon = document.getElementById('eye-icon-toggle');
        document.getElementById('eye-icon').addEventListener('click', function () {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });

        // Soumission AJAX du formulaire
        const form = document.getElementById('login-form');
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const email = document.getElementById('floatingInput').value;
            const password = document.getElementById('floatingPassword').value;
            const remember = document.getElementById('remember-me').checked;

            const errorDiv = document.getElementById('error-message');
            errorDiv.classList.add('d-none');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, remember })
                });

                const result = await response.json();

                if (result.success) {
                    if (result.role === 'admin') {
                        window.location.href = 'admin_dashboard.php';
                    } else if (result.role === 'agent') {
                        window.location.href = 'agent_dashboard.php';
                    }
                } else {
                    errorDiv.textContent = result.message;
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error(error);
                errorDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
                errorDiv.classList.remove('d-none');
            }
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</main>
</body>
</html>
