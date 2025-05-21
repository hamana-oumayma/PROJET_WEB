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
    $id = $_POST['id_utilisateur'];
    $type = $_POST['type'];
    $action = $_POST['action'];

    try {
        // Déterminer la table cible
        $table = ($type === 'etudiant') ? 'etudiants' : 'entreprises';
        
      if ($action === 'approve') {
    // Valider le compte
    $id_column = ($type === 'etudiant') ? 'id_etudiant' : 'id_entreprise';
    $stmt = $conn->prepare("UPDATE $table SET est_valide = 1 WHERE $id_column = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
        
    $_SESSION['message'] = "Le compte a été validé avec succès!";
}elseif ($action === 'reject') {
            // Supprimer le compte
            $stmt = $conn->prepare("DELETE FROM $table WHERE id_$type = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $_SESSION['message'] = "Le compte a été rejeté et supprimé.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'opération: " . $e->getMessage();
    }
}

header("Location: admin.php");
exit();
?>