<?php
session_start();
require_once __DIR__ . '/../database.php';

// Activation du débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialisation des variables
$candidatures = [];
$offre = null;

// Vérification du paramètre id_offre
$id_offre = isset($_GET['id_offre']) ? (int)$_GET['id_offre'] : 0;


try {
    $db = (new Database())->connect();
    
    // Vérifier que l'offre appartient à l'entreprise
    $stmt = $db->prepare("SELECT titre FROM offres_stage WHERE id_offre = ? AND id_entreprise = ?");
    $stmt->execute([$id_offre, $_SESSION['user_id']]);
    $offre = $stmt->fetch();
    
    if ($offre) {
        // Récupérer les candidatures
        $stmt = $db->prepare("SELECT c.*, e.nom, e.prenom, e.email 
                             FROM candidatures c
                             JOIN etudiants e ON c.id_etudiant = e.id_etudiant
                             WHERE c.id_offre = ?");
        $stmt->execute([$id_offre]);
        $candidatures = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatures - <?= htmlspecialchars($offre['titre'] ?? 'Offre') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .btn {
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
            font-size: 0.85em;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .btn-success { 
           background-color: #d1fae5; 
           color: #065f46;
        }
        .btn-danger { 
           background-color: #fee2e2;
            color: #991b1b;
        }
        .btn-info { 
            background-color:rgb(168, 160, 253) !important;
    color:rgb(255, 255, 255) !important;
        }
        .no-data {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 4px;
            margin-top: 20px;
            color: #6c757d;
        }
        .action-cell {
            display: flex;
            gap: 8px;
        }
         .btn-back {
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
    
<!DOCTYPE html>
<html>
<head>
    <title>Documents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<header>
    <div class="header-info">
       
        <h1> Les Candidatures </h1>
          
    </div>
    <a href="../entreprise.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Retour</a>
</header>

   

    <?php if (!empty($candidatures)): ?>
        <div style="overflow-x: auto;">
        <h3 class="section-title"><i class="fas fa-file-alt"></i> Candidatures pour: <?= htmlspecialchars($offre['titre'] ?? 'Cette offre') ?></h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <tr>
                        <th>Étudiant</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidatures as $candidature): ?>
                        <tr>
                            <td><?= htmlspecialchars($candidature['prenom'] . ' ' . htmlspecialchars($candidature['nom'])) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($candidature['email']) ?>" style="color: #3498db; text-decoration: none;">
                                    <?= htmlspecialchars($candidature['email']) ?>
                                </a>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($candidature['date_candidature'])) ?></td>
                            <td>
    <a href="documentsE.php?id_candidature=<?= $candidature['id_candidature'] ?>&id_offre=<?= $id_offre ?>" class="btn btn-info">
        <i class="fas fa-file-alt"></i> Voir documents
    </a>
</td>
<td>
    <div class="action-cell">
        <a href="changer_statut.php?id=<?= $candidature['id_candidature'] ?>&statut=accepte" class="btn btn-success" onclick="return confirmAction('accepte')">
            <i class="fas fa-check"></i> Accepter
        </a>
        <a href="changer_statut.php?id=<?= $candidature['id_candidature'] ?>&statut=refuse" class="btn btn-danger" onclick="return confirmAction('refuse')">
            <i class="fas fa-times"></i> Refuser
        </a>
    </div>
</td>
    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-data">
            <p><i class="fas fa-info-circle"></i> Aucune candidature pour cette offre pour le moment.</p>
            <a href="../entreprise.php" class="btn" style="background-color: #3498db; color: white;">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    <?php endif; ?>

    <script>
        // Confirmation avant changement de statut
        function confirmAction(action) {
            return confirm(`Confirmez-vous ${action} de cette candidature ?`);
        }
    </script>
</body>
</html>