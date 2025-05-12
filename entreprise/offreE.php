<?php
session_start();
require_once __DIR__ . '/../database.php';

// Vérification de l'authentification entreprise
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->connect();
$entreprise_id = $_SESSION['user_id'];

// Récupération des informations de l'entreprise
$stmt_entreprise = $db->prepare("SELECT nom FROM entreprises WHERE id_entreprise = ?");
$stmt_entreprise->execute([$entreprise_id]);
$entreprise = $stmt_entreprise->fetch(PDO::FETCH_ASSOC);

// Gestion des initiales pour l'avatar
$initials = 'E';
if (!empty($entreprise['nom'])) {
    $initials = strtoupper(substr(trim($entreprise['nom']), 0, 1));
}

// Récupération des offres
$query = "SELECT o.*, COUNT(c.id_candidature) as nb_candidatures 
          FROM offres_stage o
          LEFT JOIN candidatures c ON o.id_offre = c.id_offre
          WHERE o.id_entreprise = ?
          GROUP BY o.id_offre
          ORDER BY o.date_publication DESC";

$stmt = $db->prepare($query);
$stmt->execute([$entreprise_id]);
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    if ($date_debut >= $date_fin) $errors[] = "Date de fin invalide";

    if (empty($errors)) {
        $insert_query = "INSERT INTO offres_stage 
                        (id_entreprise, titre, description, date_debut, date_fin, lettre_motivation_requise)
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        if ($stmt->execute([$entreprise_id, $titre, $description, $date_debut, $date_fin, $motivation_requise])) {
            $_SESSION['success_message'] = "Offre créée avec succès!";
            header("Location: offreE.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des offres</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Styles de base */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin: 20px 0;
        }

        /* Bouton Nouvelle Offre */
        .btn-new-offer {
            padding: 12px 24px !important;
            font-size: 1.1rem !important;
            min-width: 180px !important;
            
            background-color: #fef3c7 !important;
            color: #92400e !important;
            border: none !important;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

       
        /* Modal corrigé */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            display: none;
            overflow-y: auto;
        }

        .modal-content {
            position: relative;
            margin: 2% auto;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        /* Champs de formulaire */
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            margin: 10px 0;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            
            outline: none;
        }

        /* Autres styles */
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 500;
        }
        .active { background: #dcfce7; color: #166534; }
        .expired { background: #fee2e2; color: #991b1b; }
        .btn-see {
    padding: 6px 10px;
    font-size: 0.85rem;
    min-width: auto;
    background-color: #fef3c7;
    color: #92400e;
    border: none;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.2s ease;
}

.btn-see i {
    font-size: 0.85rem;
}

.btn-see:hover {
   
    transform: translateY(-1px);
}

    </style>
</head>
<body>

<header>
    <div class="header-info">
        <div class="user-avatar"><?= $initials ?></div>
        <div>
            <h1>Gestion des offres</h1>
            <p><?= htmlspecialchars($entreprise['nom']) ?></p>
        </div>
    </div>
    <a href="../entreprise.php" class="logout-btn">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</header>

<div class="container">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="card" style="background-color: #d1fae5; color: #065f46;">
            <?= $_SESSION['success_message'] ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2><i class="fas fa-briefcase"></i> Liste des offres</h2>
            <button onclick="toggleModal(true)" 
        class="btn btn-primary btn-new-offer">
    <i class="fas fa-plus"></i> 
    Nouvelle offre
</button>
        </div>

        <?php if (!empty($offres)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Période</th>
                        <th>Candidatures</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offres as $offre): 
                        $isActive = strtotime($offre['date_fin']) > time();
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($offre['titre']) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($offre['date_debut'])) ?> - 
                                <?= date('d/m/Y', strtotime($offre['date_fin'])) ?>
                            </td>
                            <td><?= $offre['nb_candidatures'] ?></td>
                            <td>
                                <span class="status-badge <?= $isActive ? 'active' : 'expired' ?>">
                                    <?= $isActive ? 'Active' : 'Expirée' ?>
                                </span>
                            </td>
                            <td>
                            <a href="candidatures.php?id_offre=<?= $offre['id_offre'] ?>" 
   class="btn-see">
   <i class="fas fa-eye"></i> Voir
</a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-offers">Aucune offre publiée pour le moment</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de création d'offre -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-file-contract"></i> Nouvelle offre de stage</h3>
            <span class="close-btn" onclick="toggleModal(false)">&times;</span>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="titre">Titre de l'offre *</label>
                <input type="text" id="titre" name="titre" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description détaillée *</label>
                <textarea id="description" name="description" rows="5" required></textarea>
            </div>
            
            <div class="date-grid">
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
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="toggleModal(false)">
                    Annuler
                </button>
                <button type="submit" name="creer_offre" class="btn btn-primary">
                    <i class="fas fa-save"></i> Publier l'offre
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(show) {
    const modal = document.getElementById('createModal');
    modal.style.display = show ? 'block' : 'none';
    document.body.style.overflow = show ? 'hidden' : 'auto';
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

// Fermeture modale
window.onclick = function(event) {
    if (event.target === document.getElementById('createModal')) {
        toggleModal(false);
    }
}
</script>

</body>
</html>