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

$id_offre = $_GET['id'] ?? null;

if (!$id_offre) {
    header("Location: admin.php");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT o.*, e.nom AS entreprise_nom 
                           FROM offres_stage o 
                           JOIN entreprises e ON o.id_entreprise = e.id_entreprise 
                           WHERE o.id_offre = :id");
    $stmt->bindParam(':id', $id_offre);
    $stmt->execute();
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        throw new Exception("Offre non trouvée");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de l'offre</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/page2.css">
    <style>
        
        .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: var(--primary-color);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    margin-top: 20px;
}

.btn-back:hover {
    background-color: #15294a;
    transform: translateY(-2px);
}
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color:rgb(241, 244, 248);
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
          h2 {
            color:rgb(60, 65, 71);
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .offer-details {
            margin: 20px 0;
        }
        
        .detail-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row strong {
            color: #555;
            display: block;
            margin-bottom: 5px;
        }
        
        .detail-row p {
            margin-top: 5px;
            line-height: 1.6;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
           <header>
    <div class="header-info">
        
            <h1>Les Offres</h1>
            
    
    </div>
    <a href="admin.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</header>
    <div class="container">
        <h2><?= htmlspecialchars($offer['titre']) ?></h2>
        
        <div class="offer-details">
            <div class="detail-row">
                <strong>Entreprise:</strong>
                <span><?= htmlspecialchars($offer['entreprise_nom']) ?></span>
            </div>
            <div class="detail-row">
                <strong>Description:</strong>
                <p><?= nl2br(htmlspecialchars($offer['description'])) ?></p>
            </div>
            <div class="detail-row">
                <strong>Période:</strong>
                <span><?= htmlspecialchars($offer['date_debut'] . ' au ' . $offer['date_fin']) ?></span>
            </div>
            <div class="detail-row">
                <strong>Date publication:</strong>
                <span><?= htmlspecialchars($offer['date_publication']) ?></span>
            </div>
        </div>
        
        <a href="admin.php" class="back-btn">Retour à l'administration</a>
    </div>
</body>
</html>