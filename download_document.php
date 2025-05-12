<?php
session_start();
include('database.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$document_id = $_GET['id'] ?? null;

if (!$document_id) {
    die("Document ID manquant");
}

$db = new Database();
$conn = $db->connect();

// Vérifier que l'utilisateur a bien accès à ce document
$query = "SELECT d.chemin_fichier, d.type_document 
          FROM documents d
          JOIN candidatures c ON d.id_candidature = c.id_candidature
          WHERE d.id_document = :document_id AND c.id_etudiant = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':document_id', $document_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document || !file_exists($document['chemin_fichier'])) {
    die("Document non trouvé ou accès refusé");
}

// Envoyer le fichier au navigateur
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($document['chemin_fichier']).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($document['chemin_fichier']));
readfile($document['chemin_fichier']);
exit;
?>