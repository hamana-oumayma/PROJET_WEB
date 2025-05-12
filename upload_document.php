<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
    $db = new Database();
    $conn = $db->connect();
    
    $documentId = $_POST['id_document'];
    $typeDocument = $_POST['type_document'];
    
    // Vérifier que le document appartient bien à l'étudiant
    $stmt = $conn->prepare("SELECT d.* FROM documents d
                          JOIN candidatures c ON d.id_candidature = c.id_candidature
                          WHERE d.id_document = ? AND c.id_etudiant = ?");
    $stmt->execute([$documentId, $_SESSION['user_id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        $_SESSION['error'] = "Document introuvable ou accès non autorisé";
        header("Location: dashboard.php");
        exit();
    }
    
    // Configuration upload
    $uploadDir = 'uploads/documents/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Valider le type de fichier
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    $fileType = $_FILES['document_file']['type'];
    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['error'] = "Type de fichier non autorisé. Seuls les PDF et Word sont acceptés.";
        header("Location: dashboard.php");
        exit();
    }
    
    // Générer un nom de fichier unique
    $extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
    $fileName = 'doc_' . $documentId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    // Supprimer l'ancien fichier s'il existe
    if (!empty($document['chemin_fichier']) && file_exists($document['chemin_fichier'])) {
        unlink($document['chemin_fichier']);
    }
    
    // Déplacer le nouveau fichier
    if (move_uploaded_file($_FILES['document_file']['tmp_name'], $targetPath)) {
        // Mettre à jour la base de données (sans notes)
        $updateStmt = $conn->prepare("UPDATE documents 
                                    SET chemin_fichier = ?, type_document = ?, date_upload = NOW() 
                                    WHERE id_document = ?");
        $updateStmt->execute([$targetPath, $typeDocument, $documentId]);
        
        $_SESSION['message'] = "Document téléversé avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors du téléversement du document";
    }
}

header("Location: dashboard.php");
exit();
?>