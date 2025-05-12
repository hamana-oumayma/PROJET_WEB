<?php
session_start();
include('database.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$id_offre = $_POST['id_offre'];

$db = new Database();
$conn = $db->connect();

// 1. Créer la candidature
$query = "INSERT INTO candidatures (id_offre, id_etudiant, statut, date_candidature) 
          VALUES (:id_offre, :id_etudiant, 'en_attente', NOW())";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id_offre', $id_offre);
$stmt->bindParam(':id_etudiant', $user_id);
$stmt->execute();

$id_candidature = $conn->lastInsertId();

// 2. Enregistrer les documents
function enregistrerDocument($conn, $id_candidature, $type, $file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $file_name = uniqid() . '_' . basename($file["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            $query = "INSERT INTO documents (id_candidature, type_document, chemin_fichier) 
                      VALUES (:id_candidature, :type, :chemin)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id_candidature', $id_candidature);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':chemin', $target_file);
            $stmt->execute();
        }
    }
}

// Enregistrer chaque document
enregistrerDocument($conn, $id_candidature, 'cv', $_FILES['cv_file']);
enregistrerDocument($conn, $id_candidature, 'lettre_motivation', $_FILES['motivation_file']);

if (isset($_FILES['notes_file']) && $_FILES['notes_file']['error'] === UPLOAD_ERR_OK) {
    enregistrerDocument($conn, $id_candidature, 'releve_notes', $_FILES['notes_file']);
}

// Enregistrer le message si fourni
if (!empty($_POST['message'])) {
    $query = "UPDATE candidatures SET message = :message WHERE id_candidature = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':message', $_POST['message']);
    $stmt->bindParam(':id', $id_candidature);
    $stmt->execute();
}

$_SESSION['message'] = "Votre candidature a été envoyée avec succès!";
header("Location: dashboard.php");
exit();
?>