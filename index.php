<?php
  session_start();
  include 'config.php';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $email = $_POST['email'];
      $password = $_POST['password'];
      $remember = isset($_POST['remember-me']); // Vérifier si "Se rappeler de moi" est coché

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
                  // Générer un token unique pour l'utilisateur
                  $token = bin2hex(random_bytes(16));
                  // Enregistrer ce token dans un cookie
                  setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), "/", "", false, true); // cookie valide pendant 30 jours

                  // Enregistrer le token dans la base de données pour l'utilisateur
                  $stmt = $pdo->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                  $stmt->execute([':token' => $token, ':id' => $user['id']]);
                }

                // Redirection en fonction du rôle
                if ($user['role'] === 'admin') {
                  header('Location: admin_dashboard.php');
                  exit;
                } elseif ($user['role'] === 'agent') {
                  header('Location: agent_dashboard.php');
                  exit;
                }
              } else {
              $error_message = "Email ou mot de passe incorrect.";
            }
          } catch (PDOException $e) {
          $error_message = "Erreur lors de la connexion : " . $e->getMessage();
        }
    }

    // Vérifier si l'utilisateur est déjà authentifié avec le cookie "remember_me"
    if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    // Rechercher l'utilisateur par le token
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = :token");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Connecter l'utilisateur
        $_SESSION['user'] = [
            'id' => $user['id'],
            'role' => $user['role']
        ];

        // Rediriger selon le rôle de l'utilisateur
        if ($user['role'] === 'admin') {
            header('Location: admin_dashboard.php');
            exit;
        } elseif ($user['role'] === 'agent') {
            header('Location: agent_dashboard.php');
            exit;
        }
      }
    }
?>

<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Call Center Nathalie Gift">
    <title>Connexion · Call Center Nathalie Gift</title>

    <!-- Bootstrap core CSS -->
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
    <br>
  </head>
  <body class="text-center">
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
      <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Call Center Nathalie Gift🎁🎀🎉🎊🛍️</a>
      </div>
    </nav>
    <main class="form-signin mt-5">
      <form method="POST">
        <img class="mb-4" src="gift.png" alt="" width="300" height="300">
        <h1 class="h3 mb-3 fw-normal">Veuillez vous connecter</h1>

        <?php if (isset($error_message)): ?>
          <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>
        <!-- Champ Email -->
        <div class="form-floating mb-3">
          <input type="email" name="email" class="form-control" id="floatingInput" placeholder="name@example.com" required>
          <label for="floatingInput">Adresse Email</label>
        </div>
        <!-- mot de passe -->
        <div class="form-floating mb-3 position-relative">
          <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Mot de passe" required>
            <label for="floatingPassword">Mot de passe</label>
  
            <!-- Icône de l'œil pour voir/cacher le mot de passe -->
            <span class="position-absolute top-50 end-0 translate-middle-y" style="cursor: pointer; border: none; background-color: transparent;" id="eye-icon">
              <i class="bi bi-eye" id="eye-icon-toggle"></i>
            </span>
          </div>
          <!-- Se rappeler de moi -->
          <div class="checkbox mb-3">
          <label>
            <input type="checkbox" name="remember-me" value="remember-me"> Se rappeler de moi
          </label>
        </div>
        <button class="w-100 btn btn-lg btn-primary" type="submit">Connexion</button>
        <p class="mt-5 mb-3 text-muted">&copy; 2024</p>
      </form>
      <script>
        // Sélectionne l'élément du champ de mot de passe et de l'icône
        const passwordField = document.getElementById('floatingPassword');
        const eyeIcon = document.getElementById('eye-icon-toggle');
  
        // Ajoute un écouteur d'événements pour changer le type de champ et l'icône
        document.getElementById('eye-icon').addEventListener('click', function() {
        // Vérifie si le type du champ est 'password' ou 'text'
        if (passwordField.type === 'password') {
        passwordField.type = 'text'; // Affiche le mot de passe
        eyeIcon.classList.remove('bi-eye'); // Change l'icône en œil ouvert
        eyeIcon.classList.add('bi-eye-slash'); // Ajoute l'icône œil barré
        } else {
        passwordField.type = 'password'; // Cache le mot de passe
        eyeIcon.classList.remove('bi-eye-slash'); // Change l'icône en œil barré
        eyeIcon.classList.add('bi-eye'); // Ajoute l'icône œil ouvert
        }
        });
      </script>
    </main>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  </body>
</html>
