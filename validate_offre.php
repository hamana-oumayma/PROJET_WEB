<?php
session_start();

// Vérification des droits admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include('database.php');
$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_offre = $_POST['id_offre'];
    $action = $_POST['action'];

    try {
        if ($action === 'approve') {
            // Valider l'offre
            $stmt = $conn->prepare("UPDATE offres_stage SET est_valide = 1 WHERE id_offre = :id");
            $stmt->bindParam(':id', $id_offre);
            $stmt->execute();
            
            $_SESSION['message'] = "L'offre a été validée avec succès!";
        } elseif ($action === 'reject') {
            // Supprimer l'offre
            $stmt = $conn->prepare("DELETE FROM offres_stage WHERE id_offre = :id");
            $stmt->bindParam(':id', $id_offre);
            $stmt->execute();
            
            $_SESSION['message'] = "L'offre a été rejetée et supprimée.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'opération: " . $e->getMessage();
    }
}

header("Location: admin.php");
exit();
?>