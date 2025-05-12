<?php
session_start();
require_once __DIR__ . '/../database.php';

$id_candidature = filter_input(INPUT_GET, 'id_candidature', FILTER_VALIDATE_INT);
$id_offre = filter_input(INPUT_GET, 'id_offre', FILTER_VALIDATE_INT);

if (!$id_candidature || !$id_offre) {
    header("Location: ../entreprise.php");
    exit();
}

$db = (new Database())->connect();

// Récupérer les documents + infos étudiant
$stmt = $db->prepare("
    SELECT d.*, e.nom, e.prenom
    FROM documents d
    JOIN candidatures c ON d.id_candidature = c.id_candidature
    JOIN etudiants e ON c.id_etudiant = e.id_etudiant
    WHERE d.id_candidature = ?
");
$stmt->execute([$id_candidature]);
$documents = $stmt->fetchAll();

$studentName = '';
if (!empty($documents)) {
    $studentName = htmlspecialchars($documents[0]['prenom'] . ' ' . $documents[0]['nom']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Documents de l'Étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
    body a {
        display: inline-flex;
       
        align-items: center;
        gap: 6px;
        background-color: #d1fae5;
    color: #065f46;
        padding: 8px 15px;
        margin: 5px;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        font-size: 15px;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.2s;
    }
    
    body a i {
        font-size: 16px;
    }
    </style>
</head>
<body>
<header>
    <div class="header-info">
        <h1>Documents de la candidature</h1>
    </div>
    <a href="candidatures.php?id_offre=<?= $id_offre ?>" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Retour</a>
</header>

<?php if ($documents): ?>
    <h2 class="section-title"><i class="fas fa-file-alt"></i> Documents de : <?= $studentName ?></h2>
    <div class="card">
        <table>
            <thead>
                <tr>
            <th>Nom de l'étudiant</th>
            <th>Type de document</th>
            <th>Télécharger</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($documents as $doc): ?>
        <tr>
            <td><?= htmlspecialchars($doc['prenom'] . ' ' . $doc['nom']) ?></td>
            <td><?= htmlspecialchars($doc['type_document']) ?></td>
            <td>
                <a href="../uploads/<?= htmlspecialchars($doc['chemin_fichier']) ?>" download>
                    <i class="fas fa-download"></i> Télécharger
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="text-align:center;">Aucun document disponible</p>
<?php endif; ?>

</body>
</html>
