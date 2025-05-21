<?php
session_start();
include('database.php');
$db = new Database();
$conn = $db->connect();

//  TEST 
if (!isset($_SESSION['user_id']) || !isset($_SESSION['type']) || $_SESSION['type'] !== 'entreprise') {

    // Simuler une entreprise connectée pour test
    $_SESSION['user_id'] = $entreprise_id; // ID récupéré en base pour cette entreprise
$_SESSION['type'] = 'entreprise';

}

// Vérification entreprise connectée
if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'entreprise') {
    header("Location: login.html");
    exit();
}

$query = "SELECT a.note, a.commentaire, a.date_avis, e.nom, e.prenom 
          FROM avis_entreprises a
          JOIN etudiants e ON a.id_etudiant = e.id_etudiant
          WHERE a.id_entreprise = :id_entreprise
          ORDER BY a.date_avis DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id_entreprise', $_SESSION['user_id']);
$stmt->execute();
$avis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/page2.css">
    <title>Avis étudiants</title>
    <style>
        .avis-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        .initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .note {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .commentaire {
            padding-left: 55px;
            color: #666;
        }
        .avis-date {
            color: #999;
            font-size: 0.9em;
        }
        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
        }
        h2 {
            margin-top: 30px;
        }/* Pagination (si nécessaire dans le futur) */
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination-item {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background-color: white;
    color: var(--primary-color);
    text-decoration: none;
    transition: all 0.2s;
}

.pagination-item:hover, .pagination-item.active {
    background-color: var(--primary-color);
    color: white;
}:root {
    --primary-color: #1e3a5f;
    --secondary-color: rgb(246, 218, 59);
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --light-bg: #f8fafc;
    --dark-text: #1e293b;
}

body {
    font-family: 'Roboto', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f0f4f8;
    color: var(--dark-text);
}

/* Layout principal */
.dashboard-container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px;
}

/* En-tête de page */
h2 {
    color: var(--primary-color);
    margin-bottom: 30px;
    font-size: 26px;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Icône dans l'en-tête */
h2 i {
    color: var(--primary-color);
}

/* Carte principale */
.card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    padding: 25px;
    margin-bottom: 30px;
}

/* Liste des avis */
.avis-list {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

/* Élément d'avis individuel */
.avis-item {
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 25px;
    margin-bottom: 5px;
}

.avis-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

/* En-tête de l'avis avec info utilisateur et note */
.avis-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

/* Informations de l'utilisateur */
.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

/* Avatar avec initiales */
.initials {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Nom de l'utilisateur */
.user-name {
    font-weight: 500;
    font-size: 16px;
    color: var(--dark-text);
    margin-bottom: 4px;
}

/* Date de l'avis */
.avis-date {
    color: #64748b;
    font-size: 13px;
}

/* Note avec étoiles */
.note {
    color: var(--warning-color);
    font-size: 20px;
    letter-spacing: 2px;
}

/* Contenu du commentaire */
.commentaire {
    color: #475569;
    line-height: 1.7;
    font-size: 15px;
    padding-left: 63px; /* Aligné avec le contenu après l'avatar */
}

/* Message quand il n'y a pas d'avis */
.alert {
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    font-size: 16px;
}

.alert-info {
    background-color: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

/* Bouton de retour */
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

/* Stats récapitulatives en haut de page */
.stats-summary {
    margin-bottom: 30px;
    background-color: #f8fafc;
    border-left: 4px solid var(--primary-color);
}

.stats-grid {
    display: flex;
    justify-content: space-around;
    gap: 20px;
}

.stat-recap {
    text-align: center;
    padding: 15px;
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 5px;
}

.stat-label {
    color: #64748b;
    font-size: 14px;
}

.rating-stars {
    margin-top: 8px;
}

.text-warning {
    color: var(--warning-color);
}

.text-muted {
    color: #cbd5e1;
}

/* Animation subtile sur les cartes d'avis */
.avis-item {
    transition: transform 0.2s ease-in-out;
}

.avis-item:hover {
    transform: translateX(5px);
}

/* Responsive design */
@media (max-width: 768px) {
    .avis-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .note {
        margin-top: 10px;
        margin-left: 63px;
    }
    
    .commentaire {
        padding-left: 0;
        margin-top: 15px;
    }
}

@media (max-width: 480px) {
    .dashboard-container {
        padding: 0 15px;
    }
    
    .card {
        padding: 15px;
    }
    
    .user-info {
        gap: 10px;
    }
    
    .initials {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
}
    </style>
</head>
<body>
    <header>
    <div class="header-info">
        
            <h1>Avis</h1>
            
    
    </div>
    <a href="entreprise.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</header>

    <div class="dashboard-container">
        <h2><i class="fas fa-comment-dots"></i> Avis des étudiants</h2>
        
        <?php if (empty($avis)): ?>
            <div class="alert alert-info">Aucun avis pour le moment</div>
        <?php else: ?>
            <div class="card">
                <div class="avis-list">
                    <?php foreach ($avis as $avisItem): ?>
                        <div class="avis-item">
                            <div class="avis-header">
                                <div class="user-info">
                                    <div class="initials"><?= strtoupper(substr($avisItem['prenom'], 0, 1) . substr($avisItem['nom'], 0, 1)) ?></div>
                                    <div>
                                        <div class="user-name"><?= htmlspecialchars($avisItem['prenom'] . ' ' . $avisItem['nom']) ?></div>
                                        <div class="avis-date"><?= date('d/m/Y H:i', strtotime($avisItem['date_avis'])) ?></div>
                                    </div>
                                </div>
                                <div class="note">
                                    <?= str_repeat('★', $avisItem['note']) . str_repeat('☆', 5 - $avisItem['note']) ?>
                                </div>
                            </div>
                            <div class="commentaire">
                                <?= nl2br(htmlspecialchars($avisItem['commentaire'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
