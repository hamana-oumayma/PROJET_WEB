<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

include('../database.php');
$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];

// Requête adaptée à votre structure de base de données
$query = "SELECT 
            nom AS nom_entreprise, 
            email, 
            date_inscription, 
            adresse,
            telephone
          FROM entreprises 
          WHERE id_entreprise = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    die("Entreprise non trouvée");
}

// Pour les initiales - version robuste
$mots = array_filter(explode(' ', trim($entreprise['nom_entreprise'])));
$initials = '';

if (count($mots) > 0) {
    $initials .= strtoupper(substr($mots[0], 0, 1));
    
    if (count($mots) > 1) {
        $initials .= strtoupper(substr(end($mots), 0, 1));
    }
}

// Si aucun mot, utiliser une initiale par défaut
if (empty($initials)) {
    $initials = 'E'; // E pour Entreprise
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil Entreprise</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .company-logo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color:rgb(255, 255, 255);
            color: #1e3a5f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: bold;
            margin-right: 20px;
        }
        .logout-btn {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
    </style>
</head>
<body>
<header>
    <div class="header-info">
        <div class="company-logo"><?= $initials ?></div>
        <div>
            <h1>Profil Entreprise</h1>
            <h4>Bienvenue, <?= htmlspecialchars($entreprise['nom_entreprise']) ?> !</h4>
        </div>
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Se déconnecter</a>
</header>

<nav>
    
    <ul>
        <li><a href="../entreprise.php"><i class="fas fa-home"></i> Tableau de bord</a></li>
        <li><a href="offreE.php"><i class="fas fa-briefcase"></i> Gérer les offres</a></li>
        <li><a href="profilE.php" class="active"><i class="fas fa-building"></i> Profil Entreprise</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h3 class="section-title"><i class="fas fa-building"></i> Informations de l'entreprise</h3>
    <div class="card">
        <div class="company-info">
        <div class="actions" style="margin-top: 20px;">
            <a href="edit_profil.php" class="action-btn" style=" background-color: #d1fae5;
    color: #065f46;">
                <i class="fas fa-edit"></i> Modifier le profil
            </a>
        </div>
    
            <table>
           
                <tr><th>Nom de l'entreprise</th><td><?= htmlspecialchars($entreprise['nom_entreprise']) ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($entreprise['email']) ?></td></tr>
                <tr><th>Téléphone</th><td><?= htmlspecialchars($entreprise['telephone'] ?? 'Non spécifié') ?></td></tr>
                <tr><th>Adresse</th><td><?= htmlspecialchars($entreprise['adresse'] ?? 'Non spécifiée') ?></td></tr>
                <tr><th>Date d'inscription</th><td><?= htmlspecialchars($entreprise['date_inscription']) ?></td></tr>
            </table>
        </div>
        
       
</div>

</body>
</html>