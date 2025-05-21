<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

include('database.php');
$db = new Database();
$conn = $db->connect();

// Récupérer l'ID de l'avis depuis l'URL
$id_avis = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_avis) {
    $_SESSION['error'] = "ID d'avis invalide";
    header("Location: mes_avis.php");
    exit();
}

try {
    // Vérifier que l'avis appartient à l'étudiant
    $checkQuery = "SELECT id_avis 
                   FROM avis_entreprises 
                   WHERE id_avis = :id_avis 
                   AND id_etudiant = :id_etudiant";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([
        ':id_avis' => $id_avis,
        ':id_etudiant' => $_SESSION['user_id']
    ]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "Vous n'avez pas la permission de supprimer cet avis";
        header("Location: mes_avis.php");
        exit();
    }

    // Suppression de l'avis
    $deleteQuery = "DELETE FROM avis_entreprises 
                    WHERE id_avis = :id_avis 
                    AND id_etudiant = :id_etudiant";
    
    $stmt = $conn->prepare($deleteQuery);
    $stmt->execute([
        ':id_avis' => $id_avis,
        ':id_etudiant' => $_SESSION['user_id']
    ]);

    $_SESSION['success'] = "Avis supprimé avec succès";

} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur technique : " . $e->getMessage();
}

header("Location: mes_avis.php");
exit();
?>