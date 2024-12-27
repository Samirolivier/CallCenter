<?php
    // Inclure la configuration
    include 'config.php';
    session_start();

    // Vérification si l'utilisateur est connecté et est agent
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'agent') {
        header('Location: index.php');
        exit;
    }

    // Récupérer la liste des cadeaux disponibles
    try {
        $cadeaux = $pdo->query("SELECT id, nom FROM cadeaux")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération des cadeaux : " . $e->getMessage();
    }

    // Gestion de la soumission du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $telephone = $_POST['telephone'];
        $email = $_POST['email'];
        $adresse = $_POST['adresse'];
        $cadeau_id = $_POST['cadeau_id'];
        $texte = $_POST['texte'];
        $date_estimee_livraison = $_POST['date_estimee_livraison'];
        $express = isset($_POST['express']) ? 1 : 0;
        $prix = $_POST['prix'];
        $paye = isset($_POST['paye']) ? 1 : 0;
        $observation = $_POST['observation'];

        try {
            $stmt = $pdo->prepare("INSERT INTO receptions (nom, prenom, telephone, email, adresse, cadeau_id, texte, date_estimee_livraison, express, prix, paye, observation) 
                                VALUES (:nom, :prenom, :telephone, :email, :adresse, :cadeau_id, :texte, :date_estimee_livraison, :express, :prix, :paye, :observation)");
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':telephone' => $telephone,
                ':email' => $email,
                ':adresse' => $adresse,
                ':cadeau_id' => $cadeau_id,
                ':texte' => $texte,
                ':date_estimee_livraison' => $date_estimee_livraison,
                ':express' => $express,
                ':prix' => $prix,
                ':paye' => $paye,
                ':observation' => $observation,
            ]);
            $success = "Données enregistrées avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }

    // Gestion de la pagination
    $limit = 10; // Nombre d'enregistrements par page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Préparer les paramètres
    $filters = [];
    $whereClauses = [];

    // Ajouter des filtres
    if (!empty($_GET['nom'])) {
        $filters['nom'] = $_GET['nom'];
        $whereClauses[] = "r.nom LIKE :nom";
    }
    if (!empty($_GET['prenom'])) {
        $filters['prenom'] = $_GET['prenom'];
        $whereClauses[] = "r.prenom LIKE :prenom";
    }
    if (!empty($_GET['telephone'])) {
        $filters['telephone'] = $_GET['telephone'];
        $whereClauses[] = "r.telephone LIKE :telephone";
    }
    if (!empty($_GET['email'])) {
        $filters['email'] = $_GET['email'];
        $whereClauses[] = "r.email LIKE :email";
    }
    if (!empty($_GET['cadeau'])) {
        $filters['cadeau_id'] = $_GET['cadeau'];
        $whereClauses[] = "r.cadeau_id = :cadeau_id";
    }
    if (!empty($_GET['traitement'])) {
        $filters['traitement'] = $_GET['traitement'];
        $whereClauses[] = "
            EXISTS (
                SELECT 1 
                FROM traitements t
                WHERE t.reception_id = r.id
                AND TRIM(t.statut) = :traitement
                AND t.id = (
                    SELECT MAX(t2.id) FROM traitements t2 WHERE t2.reception_id = r.id
                )
            )";
    }


    // Générer la clause WHERE
    $whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // Construire la requête
    $sql = "SELECT r.*, c.nom AS cadeau_nom FROM receptions r
            LEFT JOIN cadeaux c ON r.cadeau_id = c.id
            $whereSql
            ORDER BY r.date_saisie DESC
            LIMIT :offset, :limit";

    // Préparer la requête SQL
    $stmt = $pdo->prepare($sql);

    // Lier les paramètres
    foreach ($filters as $key => $value) {
        if (in_array($key, ['nom', 'prenom', 'telephone', 'email'])) {
            $stmt->bindValue(":$key", "%$value%", PDO::PARAM_STR);
        } elseif (in_array($key, ['cadeau_id', 'traitement'])) {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }
    }
    // Lier les paramètres de pagination
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

    // Débogage : Affichage de la requête générée
    error_log("SQL: $sql");
    foreach ($filters as $key => $value) {
        error_log("$key: $value");
    }
    // Exécuter la requête
    $stmt->execute();
    $receptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Compte total pour la pagination
    $totalSql = "SELECT COUNT(*) FROM receptions r $whereSql";
    $totalStmt = $pdo->prepare($totalSql);

    foreach ($filters as $key => $value) {
        if (in_array($key, ['nom', 'prenom', 'telephone', 'email'])) {
            $totalStmt->bindValue(":$key", "%$value%", PDO::PARAM_STR);
        } elseif (in_array($key, ['cadeau_id', 'traitement'])) {
            $totalStmt->bindValue(":$key", $value, PDO::PARAM_INT);
        }
    }

    $totalStmt->execute();
    $totalRecords = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    if (!isset($_SESSION['user'])) {
        // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
        header('Location: index.php');
        exit;
    }

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Vueport pour la responsivité -->
    <title>Tableau de bord Agent</title>
    <link rel="stylesheet" href="assets/dist/css/bootstrap.min.css">
</head>
<style>
    .compact-table {
        font-size: 0.85rem; /* Réduction de la taille de police */
    }
    .compact-table th, .compact-table td {
        padding: 0.3rem; /* Réduction de l'espacement dans les cellules */
        vertical-align: middle; /* Aligne le contenu au milieu verticalement */
        word-wrap: break-word; /* S'assure que le texte se divise sur plusieurs lignes si nécessaire */
    }
    @media (max-width: 768px) {
        .compact-table th, .compact-table td {
            font-size: 0.75rem; /* Encore plus petit sur les écrans étroits */
            padding: 0.2rem;
        }
    }
</style>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Bienvenue sur le Tableau de bord Agent</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="d-flex align-items-center ms-auto">
                <span class="badge bg-primary me-3">Rôle : Agent</span>
                <a href="logout.php" class="btn btn-danger">Déconnexion</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h1 class="text-center">Saisir les données</h1>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" name="nom" id="nom" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="prenom" class="form-label">Prénom</label>
                <input type="text" name="prenom" id="prenom" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="telephone" class="form-label">Téléphone</label>
                <input type="text" name="telephone" id="telephone" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control">
            </div>
            <div class="col-md-12">
                <label for="adresse" class="form-label">Adresse</label>
                <textarea name="adresse" id="adresse" class="form-control"></textarea>
            </div>
            <div class="col-md-6">
                <label for="cadeau_id" class="form-label">Cadeau</label>
                <select name="cadeau_id" id="cadeau_id" class="form-select" required>
                    <option value="">Sélectionner un cadeau</option>
                    <?php foreach ($cadeaux as $cadeau): ?>
                        <option value="<?= htmlspecialchars($cadeau['id']) ?>"><?= htmlspecialchars($cadeau['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="texte" class="form-label">Texte</label>
                <textarea name="texte" id="texte" class="form-control"></textarea>
            </div>
            <div class="col-md-6">
                <label for="date_estimee_livraison" class="form-label">Date estimée de livraison</label>
                <input type="date" name="date_estimee_livraison" id="date_estimee_livraison" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="prix" class="form-label">Prix (en €)</label>
                <input type="number" step="0.01" name="prix" id="prix" class="form-control" required>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="express" id="express" class="form-check-input">
                    <label for="express" class="form-check-label">Livraison express</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="paye" id="paye" class="form-check-input">
                    <label for="paye" class="form-check-label">Payé</label>
                </div>
            </div>
            <div class="col-md-12">
                <label for="observation" class="form-label">Observation</label>
                <textarea name="observation" id="observation" class="form-control"></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
            </div>
        </div>
    </form>

    <hr>
    <h2 class="text-center">Liste des données enregistrées</h2>

    <!-- Filtres -->
    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-2">
                <input type="text" name="nom" class="form-control" placeholder="Nom"
                    value="<?= htmlspecialchars($_GET['nom'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input type="text" name="prenom" class="form-control" placeholder="Prénom"
                    value="<?= htmlspecialchars($_GET['prenom'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input type="text" name="telephone" class="form-control" placeholder="Téléphone"
                    value="<?= htmlspecialchars($_GET['telephone'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input type="email" name="email" class="form-control" placeholder="Email"
                    value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <select name="cadeau" class="form-select">
                    <option value="">Cadeau</option>
                    <?php foreach ($cadeaux as $cadeau): ?>
                        <option value="<?= $cadeau['id'] ?>" <?= isset($_GET['cadeau']) && $_GET['cadeau'] == $cadeau['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cadeau['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="traitement" class="form-select">
                    <option value="">Traitement</option>
                    <option value="en cours" <?= isset($_GET['traitement']) && $_GET['traitement'] === 'en cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="clôturé" <?= isset($_GET['traitement']) && $_GET['traitement'] === 'clôturé' ? 'selected' : '' ?>>Clôturé</option>
                    <option value="annulé" <?= isset($_GET['traitement']) && $_GET['traitement'] === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
        </div>
    </form>
    <!-- Tableau des données -->
    <div class="table-responsive">
    <table class="table table-bordered table-responsive-sm compact-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date de saisie</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th>Adresse</th>
                <th>Cadeau</th>
                <th>Prix(en €)</th>
                <th>Livraison Express</th> 
                <th>Date estimée de livraison</th> 
                <th>Texte</th> 
                <th>Payé</th>
                <th>Observation</th>
                <th>Actions</th>
                <th>Traitement</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($receptions)): ?>
            <?php foreach ($receptions as $reception): ?>
            <?php 
                // Récupérer le statut du traitement
                $stmt_traitement = $pdo->prepare("SELECT statut FROM traitements WHERE reception_id = :reception_id ORDER BY date_traitement DESC LIMIT 1");
                $stmt_traitement->bindValue(':reception_id', $reception['id'], PDO::PARAM_INT);
                $stmt_traitement->execute();
                $traitement = $stmt_traitement->fetch(PDO::FETCH_ASSOC);
                $statut_traitement = $traitement['statut'] ?? 'en cours'; // 'En cours' par défaut

                // Définir la classe de badge et le texte en fonction du statut
                $badge_class = '';
                $badge_text = '';
                switch ($statut_traitement) {
                    case 'annulé':
                        $badge_class = 'bg-danger'; // Rouge
                        $badge_text = 'Annulé';
                        break;
                    case 'clôturé':
                        $badge_class = 'bg-success'; // Vert
                        $badge_text = 'Clôturé';
                        break;
                    default:
                        $badge_class = 'bg-warning'; // Jaune
                        $badge_text = 'En cours';
                        break;
                }
                ?>

                <tr>
                    <td><?= htmlspecialchars($reception['id']) ?></td>
                    <td><?= htmlspecialchars($reception['date_saisie']) ?></td>
                    <td><?= htmlspecialchars($reception['nom']) ?></td>
                    <td><?= htmlspecialchars($reception['prenom']) ?></td>
                    <td><?= htmlspecialchars($reception['telephone']) ?></td>
                    <td><?= htmlspecialchars($reception['email']) ?></td>
                    <td><?= htmlspecialchars($reception['adresse']) ?></td>
                    <td><?= htmlspecialchars($reception['cadeau_nom'] ?? 'Non défini') ?></td>
                    <td><?= htmlspecialchars($reception['prix']) ?></td>
                    <td><?= $reception['express'] ? 'Oui' : 'Non' ?></td> <!-- Livraison express -->
                    <td><?= htmlspecialchars($reception['date_estimee_livraison']) ?></td> <!-- Date estimée de livraison -->
                    <td><?= htmlspecialchars($reception['texte']) ?></td> <!-- Texte -->
                    <td><?= $reception['paye'] ? 'Oui' : 'Non' ?></td>
                    <td><?= htmlspecialchars($reception['observation']) ?></td>
                    <td>
                        <?php if ($statut_traitement === 'en cours'): ?>
                            <!-- Bouton "Modifier" activé uniquement si le statut est "En cours" -->
                            <a href="modifier_reception.php?id=<?= $reception['id'] ?>" class="btn btn-warning btn-sm">Modifier</a>
                        <?php else: ?>
                            <!-- Bouton désactivé si le statut est "Clôturé" ou "Annulé" -->
                            <button class="btn btn-secondary btn-sm" disabled>Modifier</button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Affichage du statut avec badge coloré -->
                        <span class="badge <?= $badge_class ?>">
                            <?= htmlspecialchars(ucfirst($badge_text)) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="16" class="text-center">Aucune donnée trouvée.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <!-- Pagination -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
<script src="assets/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
