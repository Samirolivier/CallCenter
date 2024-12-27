<?php
    include 'config.php';


    $email = "admin@example.com"; // Remplacez par l'email de l'utilisateur
    $password = password_hash("adminpass", PASSWORD_BCRYPT); // Remplacez par le mot de passe de l'utilisateur
    $role = "admin"; // Choisissez entre 'admin' ou 'agent'

    try {
        // Préparer la requête pour insérer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, :role)");
        $stmt->execute([
            ':email' => $email,
            ':password' => $password,
            ':role' => $role
        ]);

        echo "Utilisateur ajouté avec succès.";
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
?>
