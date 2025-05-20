<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Connexion à la base de données
include('../database.php');
$db = new Database();
$conn = $db->connect();

// Récupérer les infos de l'étudiant
$stmt_info = $conn->prepare("SELECT nom, prenom FROM etudiants WHERE id_etudiant = :id");
$stmt_info->bindParam(':id', $user_id);
$stmt_info->execute();
$etudiant = $stmt_info->fetch(PDO::FETCH_ASSOC);

$prenom = $etudiant['prenom'] ?? '';
$nom = $etudiant['nom'] ?? '';

// Sécurité pour les initiales
$firstInitial = isset($prenom[0]) ? $prenom[0] : '';
$secondInitial = isset($nom[0]) ? $nom[0] : '';
$initials = strtoupper($firstInitial . $secondInitial);

// Récupérer les candidatures
$query = "SELECT c.id_candidature, o.titre AS poste, o.date_debut, o.date_fin, c.statut, e.nom AS entreprise
          FROM candidatures c
          JOIN offres_stage o ON c.id_offre = o.id_offre
          JOIN entreprises e ON o.id_entreprise = e.id_entreprise
          WHERE c.id_etudiant = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Candidatures</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
      
        .btn-back {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-badge.accepte { /* Retirez l'accent 'é' */
    background-color: #d1fae5;
    color: #065f46;
}

.status-badge.en_attente {
    background-color: #fef3c7;
    color: #92400e;
}

.status-badge.refuse { /* Retirez l'accent 'é' */
    background-color: #fee2e2;
    color: #991b1b;
}
        
        </style>
</head>
<body>

<header>
    <div class="header-info">
        <div class="user-avatar"><?= $initials ?></div>
        <div>
            <h1>Mes Candidatures</h1>
            <h4>Bonjour, <?= htmlspecialchars($prenom . ' ' . $nom) ?> !</h4>
        </div>
    </div>
    <a href="../dashboard.php" class="btn-back">
    <i class="fas fa-arrow-left"></i> Retour
</a>
</header>

<h3 class="section-title"><i class="fas fa-file-alt"></i> Mes Candidatures</h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                <th>Entreprise</th>
                <th>Poste</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($candidatures) > 0): ?>
                <?php foreach ($candidatures as $candidature): ?>
                <tr>
                    <td><?= htmlspecialchars($candidature['entreprise']) ?></td>
                    <td><?= htmlspecialchars($candidature['poste']) ?></td>
                    <td><?= htmlspecialchars($candidature['date_debut']) ?></td>
                    <td><?= htmlspecialchars($candidature['date_fin']) ?></td>
                    <td><span class="status-badge <?= strtolower(str_replace([' ', 'é'], ['_', 'e'], $candidature['statut'])) ?>"><?= ucfirst($candidature['statut']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">Aucune candidature trouvée.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

</body>
</html>
