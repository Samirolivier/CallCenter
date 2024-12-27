<?php
    // Inclure la configuration
    include 'config.php';
    session_start();

    // Vérification si l'utilisateur est connecté et est agent
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'agent') {
        header('Location: index.php');
        exit;
    }

    // Récupérer l'ID de la réception à modifier
    if (!isset($_GET['id'])) {
        header('Location: agent_dashboard.php'); // Rediriger si aucun ID
        exit;
    }

    $id = (int)$_GET['id'];

    // Récupérer les données existantes de la réception
    try {
        $stmt = $pdo->prepare("SELECT * FROM receptions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $reception = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reception) {
            header('Location: agent_dashboard.php');
            exit;
        }
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }

    // Récupérer la liste des cadeaux
    try {
        $cadeaux = $pdo->query("SELECT id, nom FROM cadeaux")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }

    // Traitement du formulaire de modification
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $telephone = $_POST['telephone'];
        $adresse = $_POST['adresse'];
        $cadeau_id = $_POST['cadeau_id'];
        $prix = $_POST['prix'];
        $paye = isset($_POST['paye']) ? 1 : 0;
        $observation = $_POST['observation'];
        $date_estimee_livraison = $_POST['date_estimee_livraison'];
        $express = isset($_POST['express']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("
                UPDATE receptions
                SET nom = :nom, prenom = :prenom, telephone = :telephone, adresse = :adresse,
                    cadeau_id = :cadeau_id, prix = :prix, paye = :paye, observation = :observation,
                    date_estimee_livraison = :date_estimee_livraison, express = :express
                WHERE id = :id
            ");
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':telephone' => $telephone,
                ':adresse' => $adresse,
                ':cadeau_id' => $cadeau_id,
                ':prix' => $prix,
                ':paye' => $paye,
                ':observation' => $observation,
                ':date_estimee_livraison' => $date_estimee_livraison,
                ':express' => $express,
                ':id' => $id
            ]);
            $success = "Données mises à jour avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une réception</title>
    <link rel="stylesheet" href="assets/dist/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="agent_dashboard.php">Modification sur une saisie</a>
        <a href="logout.php" class="btn btn-danger">Déconnexion</a>
    </div>
</nav>

<div class="container mt-4">
    <!-- Lien de retour -->
    <a href="agent_dashboard.php" class="btn btn-secondary mb-3">Retour sur Tableau de Bord Agent</a>
    <h1>Modifier une réception</h1>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="nom" class="form-label">Nom</label>
            <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($reception['nom']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="prenom" class="form-label">Prénom</label>
            <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($reception['prenom']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="telephone" class="form-label">Téléphone</label>
            <input type="text" name="telephone" id="telephone" class="form-control" value="<?= htmlspecialchars($reception['telephone']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="adresse" class="form-label">Adresse</label>
            <textarea name="adresse" id="adresse" class="form-control" rows="3"><?= htmlspecialchars($reception['adresse']) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="cadeau_id" class="form-label">Cadeau</label>
            <select name="cadeau_id" id="cadeau_id" class="form-select" required>
                <option value="">Sélectionnez un cadeau</option>
                <?php foreach ($cadeaux as $cadeau): ?>
                    <option value="<?= $cadeau['id'] ?>" <?= $cadeau['id'] == $reception['cadeau_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cadeau['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="prix" class="form-label">Prix</label>
            <input type="number" step="0.01" name="prix" id="prix" class="form-control" value="<?= htmlspecialchars($reception['prix']) ?>" required>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="paye" id="paye" class="form-check-input" <?= $reception['paye'] ? 'checked' : '' ?>>
            <label for="paye" class="form-check-label">Payé</label>
        </div>
        <div class="mb-3">
            <label for="observation" class="form-label">Observation</label>
            <textarea name="observation" id="observation" class="form-control" rows="3"><?= htmlspecialchars($reception['observation']) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="date_estimee_livraison" class="form-label">Date estimée de livraison</label>
            <input type="date" name="date_estimee_livraison" id="date_estimee_livraison" class="form-control" value="<?= htmlspecialchars($reception['date_estimee_livraison']) ?>" required>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="express" id="express" class="form-check-input" <?= $reception['express'] ? 'checked' : '' ?>>
            <label for="express" class="form-check-label">Livraison express</label>
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
        <a href="agent_dashboard.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>

<script src="assets/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
