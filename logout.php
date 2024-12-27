<?php
    session_start();

    // Suppression de la session
    session_unset();
    session_destroy();

    // Suppression du cookie "remember_me" si présent
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/');  // Expire le cookie en le définissant à une date passée
    }

    // Redirection vers la page d'accueil
    header('Location: index.php');
    exit;
?>
