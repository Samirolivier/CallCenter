<?php
    include 'config.php';

    try {
        // Récupérer les données du formulaire
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $telephone = $_POST['telephone'];
        $email = $_POST['email'];
        $adresse = $_POST['adresse'];
        $cadeau_id = $_POST['cadeau_id'];
        $texte = $_POST['texte'];
        $date_estimee_livraison = $_POST['date_estimee_livraison'];
        $express = isset($_POST['express']) ? 1 : 0;

        // Insérer dans la base de données
        $sql = "INSERT INTO receptions (nom, prenom, telephone, email, adresse, cadeau_id, texte, date_estimee_livraison, express)
                VALUES (:nom, :prenom, :telephone, :email, :adresse, :cadeau_id, :texte, :date_estimee_livraison, :express)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':telephone' => $telephone,
            ':email' => $email,
            ':adresse' => $adresse,
            ':cadeau_id' => $cadeau_id,
            ':texte' => $texte,
            ':date_estimee_livraison' => $date_estimee_livraison,
            ':express' => $express
        ]);

        header("Location: admin_dashboard.php?success=1");
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
?>
