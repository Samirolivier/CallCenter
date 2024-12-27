<?php
    session_start();
    include 'config.php';

    // Vérifier si l'utilisateur est connecté et est admin
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reception_id'], $_POST['statut'])) {
        $receptionId = $_POST['reception_id'];
        $newStatut = $_POST['statut'];

        try {
            // Vérifier que le statut est valide
            if (!in_array($newStatut, ['en cours', 'clôturé', 'annulé'])) {
                throw new Exception("Statut invalide.");
            }

            // Insérer ou mettre à jour le statut du traitement
            $stmt = $pdo->prepare("INSERT INTO traitements (reception_id, statut) VALUES (:reception_id, :statut)");
            $stmt->execute([
                ':reception_id' => $receptionId,
                ':statut' => $newStatut
            ]);

            // Redirection vers la page admin avec un message de succès
            header('Location: admin_dashboard.php?success=Statut modifié avec succès');
            exit;
        } catch (Exception $e) {
            // Redirection avec un message d'erreur
            header('Location: admin_dashboard.php?error=' . $e->getMessage());
            exit;
        }
    }
?>
