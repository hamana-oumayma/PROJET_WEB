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

// Récupérer les infos de l'étudiant
$query = "SELECT nom, prenom, email, date_inscription FROM etudiants WHERE id_etudiant = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$etudiant) {
    echo "Étudiant non trouvé.";
    exit();
}

// Pour les initiales
$initials = strtoupper($etudiant['prenom'][0] . $etudiant['nom'][0]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .logout-btn {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-btn {
            background-color: #d1fae5; 
            color: #065f46; 
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 10px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
<header>
    <div class="header-info">
        <div class="user-avatar"><?= $initials ?></div>
        <div>
            <h1>Mon Profil</h1>
            <h4>Bonjour, <?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?> !</h4>
        </div>
    </div>
    <div class="header-actions">

        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Se déconnecter
        </a>
    </div>
</header>

<nav>
    <ul>
        <li><a href="../dashboard.php"><i class="fas fa-home"></i> Tableau de bord</a></li>
        <li><a href="stages.php"><i class="fas fa-briefcase"></i> Offres de stage</a></li>
        <li><a href="candidatures.php"><i class="fas fa-file-alt"></i> Mes candidatures</a></li>
        <li><a href="documents.php"><i class="fas fa-file-upload"></i> Mes documents</a></li>
        <li><a href="profil.php" class="active"><i class="fas fa-user"></i> Mon profil</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h3 class="section-title"><i class="fas fa-user"></i> Mes informations</h3>
    <div class="card">
    <a href="edit_profil.php" class="edit-btn">
            <i class="fas fa-edit"></i> Modifier le profil
        </a>
        <table>
            <tr><th>Nom</th><td><?= htmlspecialchars($etudiant['nom']) ?></td></tr>
            <tr><th>Prénom</th><td><?= htmlspecialchars($etudiant['prenom']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($etudiant['email']) ?></td></tr>
            <tr><th>Date d'inscription</th><td><?= htmlspecialchars($etudiant['date_inscription']) ?></td></tr>
        </table>
        
        
    </div>
</div>

</body>
</html>