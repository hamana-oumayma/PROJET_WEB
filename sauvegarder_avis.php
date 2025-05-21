<?php
session_start();
if(!isset($_SESSION['user_id'])) die("Accès non autorisé");

include('database.php');
$db = new Database();
$conn = $db->connect();

// Vérifier si l'étudiant a déjà noté cette entreprise
$check = "SELECT * FROM avis_entreprises 
          WHERE id_etudiant = :id_etudiant 
          AND id_entreprise = :id_entreprise";
$stmt = $conn->prepare($check);
$stmt->execute([
    ':id_etudiant' => $_SESSION['user_id'],
    ':id_entreprise' => $_POST['id_entreprise']
]);

if($stmt->rowCount() > 0) {
    $_SESSION['error'] = "Vous avez déjà noté cette entreprise";
    header("Location: evaluer_entreprise.php");
    exit();
}

// Insérer le nouvel avis
$query = "INSERT INTO avis_entreprises 
          (id_etudiant, id_entreprise, note, commentaire) 
          VALUES (:id_etudiant, :id_entreprise, :note, :commentaire)";

$stmt = $conn->prepare($query);
$success = $stmt->execute([
    ':id_etudiant' => $_SESSION['user_id'],
    ':id_entreprise' => $_POST['id_entreprise'],
    ':note' => $_POST['note'],
    ':commentaire' => htmlspecialchars($_POST['commentaire'])
]);

if($success) {
    $_SESSION['success'] = "Votre avis a été enregistré !";
} else {
    $_SESSION['error'] = "Erreur lors de l'enregistrement";
}

header("Location: mes_avis.php");