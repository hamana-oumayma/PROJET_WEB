<?php
session_start();
require_once 'database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->connect();
$entreprise_id = $_SESSION['user_id'];

// Récupérer les infos de l'entreprise pour le header
$query_entreprise = "SELECT nom FROM entreprises WHERE id_entreprise = ?";
$stmt_entreprise = $db->prepare($query_entreprise);
$stmt_entreprise->execute([$entreprise_id]);
$entreprise = $stmt_entreprise->fetch(PDO::FETCH_ASSOC);

// Gestion des initiales pour l'avatar
$initials = 'E'; // Valeur par défaut
if (!empty($entreprise['nom'])) {
    $nom = trim($entreprise['nom']);
    $initials = strtoupper(substr($nom, 0, 1));
}

if (!$entreprise) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Récupération des offres de l'entreprise (sans le statut)
$query = "SELECT o.*, COUNT(c.id_candidature) as nb_candidatures 
          FROM offres_stage o
          LEFT JOIN candidatures c ON o.id_offre = c.id_offre
          WHERE o.id_entreprise = ? AND o.est_valide = 1
          GROUP BY o.id_offre
          ORDER BY o.date_publication DESC";
$stmt = $db->prepare($query);
$stmt->execute([$entreprise_id]);
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dans votre requête SQL (après la connexion)
$statsQuery = "SELECT 
    COUNT(id_offre) as total_offres,
    SUM(CASE WHEN date_fin >= CURDATE() THEN 1 ELSE 0 END) as offres_actives,
    (SELECT COUNT(id_candidature) FROM candidatures c 
     JOIN offres_stage o ON c.id_offre = o.id_offre 
     WHERE o.id_entreprise = ?) as total_candidatures
FROM offres_stage 
WHERE id_entreprise = ?";

$stmtStats = $db->prepare($statsQuery);
$stmtStats->execute([$entreprise_id, $entreprise_id]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
// Traitement de la création d'offre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_offre'])) {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $motivation_requise = isset($_POST['motivation_requise']) ? 1 : 0;

    // Validation
    $errors = [];
    if (empty($titre)) $errors[] = "Le titre est requis";
    if (empty($description)) $errors[] = "La description est requise";
    if (empty($date_debut)) $errors[] = "La date de début est requise";
    if (empty($date_fin)) $errors[] = "La date de fin est requise";
    
    if ($date_debut >= $date_fin) {
        $errors[] = "La date de fin doit être postérieure à la date de début";
    }

    if (empty($errors)) {
        $insert_query = "INSERT INTO offres_stage 
                        (id_entreprise, titre, description, date_debut, date_fin, lettre_motivation_requise, est_valide)
                        VALUES (?, ?, ?, ?, ?, ?, 0)"; // est_valide = 0 par défaut
        $stmt = $db->prepare($insert_query);
        if ($stmt->execute([$entreprise_id, $titre, $description, $date_debut, $date_fin, $motivation_requise])) {
            $_SESSION['success_message'] = "Offre soumise avec succès! Elle sera visible après validation par l'administrateur.";
            header("Location: entreprise.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Erreur lors de la création de l'offre";
        }
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Entreprise</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        :root {
            --primary-color: #1e3a5f;
            --secondary-color:rgb(246, 218, 59);
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

        header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #fff;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: bold;
        }

        .logout-btn {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        nav {
            
            padding: 10px 0;
        }

        nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }

        nav ul li {
            margin: 0 15px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s;
        }

       

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-right: 10px;
        }

        .btn-primary {
            background-color: #fef3c7;
            color: #92400e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background-color: #f1f5f9;
            color: var(--primary-color);
        }

        .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto; /* Permet le défilement si nécessaire */
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 20px auto; /* Réduire la marge haut */
    padding: 25px;
    border-radius: 10px;
    width: 80%;
    max-width: 600px;
    position: relative;
    top: 50%;
    transform: translateY(-50%); /* Centre verticalement */
}
.btn-primary {
    background-color: #fef3c7;
    color: #92400e;
   
    transition: none !important; 
}


.btn-primary:hover, .btn-primary:focus {
    background-color: #fef3c7 !important;
    color: #92400e !important;
    transform: none !important;
    box-shadow: none !important;
}
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .form-group textarea {
            min-height: 150px;
        }

       
        .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}
.stat-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.stat-card {
    position: relative;
    padding-top: 2rem;
}

.stat-icon {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    padding: 10px;
    border-radius: 50%;
    color:rgb(153, 49, 250);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
}
.stat-label {
    color: #64748b;
}


    </style>
</head>
<body>
    <header>
        <div class="header-info">
            <div class="user-avatar"><?= $initials ?></div>
            <div>
                <h1>Espace Entreprise</h1>
                <p><?= htmlspecialchars($entreprise['nom']) ?></p>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </header>

    <nav>
        <ul>
            <li><a href="entreprise.php" class="active"><i class="fas fa-home"></i> Tableau de bord</a></li>
            <li><a href="./entreprise/offreE.php"><i class="fas fa-briefcase"></i> Mes offres</a></li>
            <li><a href="./entreprise/candidatures.php"><i class="fas fa-file-alt"></i> Candidatures</a></li>
            <li><a href="./entreprise/profilE.php"><i class="fas fa-user"></i> Mon profil</a></li>
        </ul>
    </nav>

    <div class="container">
        <!-- Messages de notification -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="card" style="background-color: #d1fae5; color: #065f46;">
                <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="card" style="background-color: #fee2e2; color: #991b1b;">
                <?= $_SESSION['error_message'] ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        <div class="card">
    <h2><i class="fas fa-chart-bar"></i> Statistiques</h2>
    <div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-briefcase stat-icon"></i>
        <div class="stat-value"><?= $stats['total_offres'] ?></div>
        <div class="stat-label">Offres publiées</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle stat-icon"></i>
        <div class="stat-value"><?= $stats['offres_actives'] ?></div>
        <div class="stat-label">Offres actives</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-file-alt stat-icon"></i>
        <div class="stat-value"><?= $stats['total_candidatures'] ?></div>
        <div class="stat-label">Candidatures</div>
    </div>
</div>
</div>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2><i class="fas fa-briefcase"></i> Mes offres de stage</h2>
                <button onclick="document.getElementById('createModal').style.display='block'" 
                        class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle offre
                </button>
            </div>
            
            <?php if (count($offres) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Dates</th>
                            <th>Candidatures</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offres as $offre): ?>
                            <tr>
                                <td><?= htmlspecialchars($offre['titre']) ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($offre['date_debut'])) ?> - 
                                    <?= date('d/m/Y', strtotime($offre['date_fin'])) ?>
                                </td>
                                <td><?= $offre['nb_candidatures'] ?></td>
                                <td>
                                <a href="entreprise/candidatures.php?id_offre=<?= $offre['id_offre'] ?>" class="btn btn-primary">
    <i class="fas fa-eye"></i> Voir 
</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; margin-top: 20px;">Vous n'avez pas encore publié d'offres de stage.</p>
            <?php endif; ?>
            </div>
    </div>
        <!-- Modal de création d'offre -->
        <div id="createModal" class="modal">
            <div class="modal-content">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fas fa-plus"></i> Nouvelle offre de stage</h3>
                    <span onclick="document.getElementById('createModal').style.display='none'" 
                          style="cursor: pointer; font-size: 1.5em;">&times;</span>
                </div>
                
                <form action="entreprise.php" method="post">
                    <div class="form-group">
                        <label for="titre">Titre de l'offre *</label>
                        <input type="text" id="titre" name="titre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description détaillée *</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="date_debut">Date de début *</label>
                            <input type="date" id="date_debut" name="date_debut" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_fin">Date de fin *</label>
                            <input type="date" id="date_fin" name="date_fin" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="motivation_requise">
                            Lettre de motivation requise
                        </label>
                    </div>
                    
                    <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                        <button type="button" onclick="document.getElementById('createModal').style.display='none'" 
                                class="btn" style="margin-right: 10px;">
                            Annuler
                        </button>
                        <button type="submit" name="creer_offre" class="btn btn-primary">
                            <i class="fas fa-save"></i> Publier l'offre
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Gestion du modal
        window.onclick = function(event) {
            if (event.target == document.getElementById('createModal')) {
                document.getElementById('createModal').style.display = "none";
            }
        }
        
        // Validation des dates
        document.querySelector('form').addEventListener('submit', function(e) {
            const debut = new Date(document.getElementById('date_debut').value);
            const fin = new Date(document.getElementById('date_fin').value);
            
            if (debut >= fin) {
                alert("La date de fin doit être postérieure à la date de début");
                e.preventDefault();
            }
        });
        // Remplacer votre gestion actuelle du modal par ceci
function toggleModal(show) {
    const modal = document.getElementById('createModal');
    if (show) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Empêche le défilement de la page
    } else {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Rétablit le défilement
    }
}

// Modifier vos événements onclick
document.querySelector('button[onclick*="createModal"]').onclick = function() {
    toggleModal(true);
};

document.querySelector('span[onclick*="createModal"]').onclick = function() {
    toggleModal(false);
};

window.onclick = function(event) {
    if (event.target == document.getElementById('createModal')) {
        toggleModal(false);
    }
};
    </script>
</body>
</html>