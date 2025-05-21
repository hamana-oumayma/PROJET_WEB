<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];


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


// Récupérer l'ID de candidature depuis l'URL si présent
$id_candidature = isset($_GET['id_candidature']) ? $_GET['id_candidature'] : null;

// Modifier la requête pour filtrer par candidature si ID fourni
$query = "SELECT d.id_document, o.titre AS offre, d.type_document, d.chemin_fichier
          FROM documents d
          JOIN candidatures c ON d.id_candidature = c.id_candidature
          JOIN offres_stage o ON c.id_offre = o.id_offre
          WHERE c.id_etudiant = :user_id";

// Ajouter le filtre si id_candidature est spécifié
if ($id_candidature) {
    $query .= " AND c.id_candidature = :id_candidature";
}

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
if ($id_candidature) {
    $stmt->bindParam(':id_candidature', $id_candidature);
}
$stmt->execute();
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Documents</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Style général des boutons d'action */
.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.3s, transform 0.2s;
}

/* Bouton Télécharger */
.download-btn {
    background-color: #fef3c7;
    color: #92400e;
}


/* Bouton Téléverser */
.upload-btn {
    background-color:rgb(186, 216, 241); /* Bleu */
    color: blue;
}
 .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
            margin: 20px auto;
            max-width: 1200px;
        }

         .btn-back {
            background-color: #1e3a5f;
            color:hsl(218, 100.00%, 98.40%);
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
        <div class="user-avatar"><?= $initials ?></div>
        <div>
            <h1>Mes Documents</h1>
            <h4>Bonjour, <?= htmlspecialchars($prenom . ' ' . $nom) ?> !</h4>
        </div>
    </div>
    <a href="../dashboard.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Retour</a>
</header>

<h3 class="section-title"><i class="fas fa-file-alt"></i> Mes documents</h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                <th>Candidature</th>
                <th>Document</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $document): ?>
            <tr>
                <td><?= htmlspecialchars($document['offre']) ?></td>
                <td><?= htmlspecialchars($document['type_document']) ?></td>
                <td>
                    <span class="status-badge <?= $document['chemin_fichier'] ? 'completed' : 'pending' ?>">
                        <?= $document['chemin_fichier'] ? 'Complété' : 'Manquant' ?>
                    </span>
                </td>
                <td>
    <?php if ($document['chemin_fichier']): ?>
        <a href="../download_document.php?id=<?= $document['id_document'] ?>" class="action-btn download-btn">
            <i class="fas fa-download"></i> Télécharger
        </a>
    <?php else: ?>
        <button class="action-btn upload-btn" data-id="<?= $document['id_document'] ?>">
            <i class="fas fa-upload"></i> Téléverser
        </button>
    <?php endif; ?>
</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

</body>
</html>