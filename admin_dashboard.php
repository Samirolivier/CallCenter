<?php
    session_start();
    include 'config.php';

    // Vérifier si l'utilisateur est connecté et est admin
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }

    // Gestion de la création d'utilisateur
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];

        try {
            // Vérifier que l'email n'existe pas déjà
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->rowCount() > 0) {
                $error = "L'email existe déjà.";
            } else {
                // Insérer l'utilisateur dans la base de données
                $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, :role)");
                $stmt->execute([
                    ':email' => $email,
                    ':password' => password_hash($password, PASSWORD_BCRYPT),
                    ':role' => $role
                ]);
                $success = "Utilisateur créé avec succès.";
            }
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }

    // Gestion de la suppression d'utilisateur
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $success = "Utilisateur supprimé avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }

    // Récupérer la liste des utilisateurs
    try {
        $users = $pdo->query("SELECT id, email, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }

    // Gestion de la création de cadeaux
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_cadeau'])) {
        $nom_cadeau = $_POST['nom_cadeau'];

        try {
            $stmt = $pdo->prepare("INSERT INTO cadeaux (nom) VALUES (:nom)");
            $stmt->execute([':nom' => $nom_cadeau]);
            $success = "Cadeau créé avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }

    // Gestion de la modification d'un cadeau
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cadeau'])) {
        $cadeauId = $_POST['cadeau_id'];
        $newNomCadeau = $_POST['nom_cadeau'];

        try {
            $stmt = $pdo->prepare("UPDATE cadeaux SET nom = :nom WHERE id = :id");
            $stmt->execute([
                ':nom' => $newNomCadeau,
                ':id' => $cadeauId
            ]);
         $success = "Cadeau modifié avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }

    // Gestion de la suppression d'un cadeau
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cadeau'])) {
        $cadeauId = $_POST['cadeau_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM cadeaux WHERE id = :id");
            $stmt->execute([':id' => $cadeauId]);
            $success = "Cadeau supprimé avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }

    // Récupérer la liste des cadeaux
    try {
        $stmt = $pdo->prepare("SELECT * FROM cadeaux ORDER BY nom");
        $stmt->execute();
        $cadeaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération des cadeaux : " . $e->getMessage();
    }

    // Pagination pour les données saisies par les agents
    $limit = 10; // Nombre d'enregistrements par page
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Définir la variable $totalPages par défaut
    $totalPages = 1; // Valeur par défaut, juste au cas où la requête échouerait

    try {
        // Récupérer les données de réception
        $stmt = $pdo->prepare("SELECT 
        r.id, r.date_saisie, r.nom, r.prenom, r.telephone, r.email, r.adresse, r.cadeau_id, 
        r.prix, r.express, r.date_estimee_livraison, r.texte, r.paye, r.observation, 
        c.nom AS cadeau 
        FROM receptions r 
        LEFT JOIN cadeaux c ON r.cadeau_id = c.id 
        ORDER BY r.date_saisie DESC 
        LIMIT :limit OFFSET :offset");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $receptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer le nombre total d'enregistrements
        $total = $pdo->query("SELECT COUNT(*) FROM receptions")->fetchColumn();
        $totalPages = ceil($total / $limit);
        } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }

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
        <a class="navbar-brand" href="#">Bienvenue sur le Tableau de bord Administrateur</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="d-flex align-items-center ms-auto">
                <span class="badge bg-primary me-3">Rôle : Administateur</span>
                <a href="logout.php" class="btn btn-danger">Déconnexion</a>
            </div>
        </div>
    </div>
</nav>

<!-- Messages d'erreur ou de succès -->
<?php if (isset($success)): ?>
        <div class="alert alert-success mt-3"><?= $success ?></div>
<?php elseif (isset($error)): ?>
        <div class="alert alert-danger mt-3"><?= $error ?></div>
<?php endif; ?>

<!-- Section Utilisateurs -->
<div class="row mb-5">
    <div class="col-lg-6 col-md-12">
        <h2>Créer un utilisateur</h2>
        <form method="POST">
            <input type="hidden" name="create_user" value="1">
            <div class="mb-3">
                <label for="email" class="form-label">Adresse E-mail</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Rôle</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="admin">Admin</option>
                    <option value="agent">Agent</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
        </form>
    </div>

    <div class="col-lg-6 col-md-12">
        <h2>Liste des utilisateurs</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']); ?></td>
                        <td><?= htmlspecialchars($user['email']); ?></td>
                        <td><?= htmlspecialchars($user['role']); ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="delete_user" value="1">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']); ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="4">Aucun utilisateur trouvé.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Section Cadeaux -->
    <div class="row mb-5">
        <div class="col-lg-6 col-md-12">
            <h2>Créer un cadeau</h2>
            <form method="POST">
                <input type="hidden" name="create_cadeau" value="1">
                <div class="mb-3">
                    <label for="nom_cadeau" class="form-label">Nom du cadeau</label>
                    <input type="text" name="nom_cadeau" id="nom_cadeau" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Créer</button>
            </form>
        </div>

        <div class="col-lg-6 col-md-12">
            <h2>Liste des cadeaux</h2>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom du cadeau</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($cadeaux)): ?>
                        <?php foreach ($cadeaux as $cadeau): ?>
                            <tr>
                                <td><?= htmlspecialchars($cadeau['id']); ?></td>
                                <td><?= htmlspecialchars($cadeau['nom']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCadeauModal<?= $cadeau['id']; ?>">Modifier</button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="delete_cadeau" value="1">
                                        <input type="hidden" name="cadeau_id" value="<?= htmlspecialchars($cadeau['id']); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">Aucun cadeau trouvé.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section Réceptions -->

    <!-- Formulaire de filtres -->
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

    <div class="row">
        <div class="col-12">
            <h2>Liste des données enregistrées</h2>
            <div class="table-responsive">
                <table class="table table-bordered">
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
                            <th>Prix (€)</th>
                            <th>Livraison Express</th>
                            <th>Date estimée</th>
                            <th>Texte</th>
                            <th>Payé</th>
                            <th>Observation</th>
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
                            $statut_traitement = $traitement['statut'] ?? 'en cours'; // Affichage uniquement de 'en cours' par défaut
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
                                <?php
                                    // Définir la couleur et le texte en fonction du statut
                                    $badge_class = '';
                                    $badge_text = '';

                                    // Appliquer les styles et le texte selon le statut
                                    switch ($statut_traitement) {
                                    case 'annulé':
                                    $badge_class = 'bg-danger';  // Rouge pour Annulé
                                    $badge_text = 'Annulé';
                                    break;
                                    case 'clôturé':
                                    $badge_class = 'bg-success'; // Vert pour Clôturé
                                    $badge_text = 'Clôturé';
                                    break;
                                    default:
                                    $badge_class = 'bg-warning'; // Jaune pour En cours
                                    $badge_text = 'En cours';
                                    break;
                                    }
                                ?>
                                <!-- Affichage du badge avec la couleur et le texte du statut -->
                                <span class="badge <?= $badge_class ?>">
                                    <?= htmlspecialchars(ucfirst($badge_text)) ?>
                                </span>
                                <!-- Formulaire pour mettre à jour le statut du traitement -->
                                <form action="changer_statut_traitement.php" method="POST" class="d-inline">
                                    <input type="hidden" name="reception_id" value="<?= $reception['id'] ?>">
                                    <select name="statut" class="form-select form-select-sm" required>
                                        <option value="en cours" <?= $statut_traitement == 'en cours' ? 'selected' : '' ?>>En cours</option>
                                        <option value="annulé" <?= $statut_traitement == 'annulé' ? 'selected' : '' ?> style="color: red;">Annulé</option>
                                        <option value="clôturé" <?= $statut_traitement == 'clôturé' ? 'selected' : '' ?> style="color: green;">Clôturé</option>
                                    </select>
                                    <button type="submit" class="btn btn-secondary btn-sm">Mettre à jour</button>
                                </form>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="14" class="text-center">Aucune donnée trouvée.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
</div>
</body>
</html>
