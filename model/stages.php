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

// Récupérer les offres de stage disponibles pour l'étudiant
$query = "SELECT o.id_offre, o.titre AS poste, o.description, o.date_debut, o.date_fin, e.nom AS entreprise
FROM offres_stage o
JOIN entreprises e ON o.id_entreprise = e.id_entreprise
WHERE o.id_offre NOT IN 
(SELECT id_offre FROM candidatures WHERE id_etudiant = :user_id)";

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres de Stage</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .btn-back {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        /* Styles modaux */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .close-btn {
            cursor: pointer;
            font-size: 24px;
            color: #666;
        }

        .postuler-btn {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }

        .file-upload {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            margin: 10px 0;
            transition: all 0.3s;
        }

        .file-upload:hover, .file-upload.dragover {
            border-color: #4CAF50;
            background-color: #f8fff8;
        }
    </style>
</head>
<body>
<header>
<div class="header-info">
        <div class="user-avatar"><?= $initials ?></div>
        <div>
            <h1>Les Offres de Stages</h1>
            <h4>Bonjour, <?= htmlspecialchars($prenom . ' ' . $nom) ?> !</h4>
        </div>
    </div>
    <a href="../dashboard.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Retour</a>
</header>

<h3 class="section-title"><i class="fas fa-file-alt"></i> Les offres disponibles </h3>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Entreprise</th>
                <th>Poste</th>
                <th>Description</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($offres) > 0): ?>
                <?php foreach ($offres as $offre): ?>
                <tr>
                    <td><?= htmlspecialchars($offre['entreprise']) ?></td>
                    <td><?= htmlspecialchars($offre['poste']) ?></td>
                    <td><?= htmlspecialchars($offre['description']) ?></td>
                    <td><?= htmlspecialchars($offre['date_debut']) ?></td>
                    <td><?= htmlspecialchars($offre['date_fin']) ?></td>
                    <td>
                        <button class="postuler-btn" onclick="openPostulerModal(<?= $offre['id_offre'] ?>)">
                            <i class="fas fa-paper-plane"></i> Postuler
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">Aucune offre de stage disponible.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal pour postuler -->
<div id="postulerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-paper-plane"></i> Postuler à cette offre</h3>
            <span class="close-btn" onclick="closePostulerModal()">&times;</span>
        </div>
        <form id="postulerForm" enctype="multipart/form-data" method="POST" action="postuler.php">
            <input type="hidden" id="offreId" name="id_offre">
            
            <div class="required-documents">
                <h4>Documents requis :</h4>
                <ul>
                    <li><i class="fas fa-check-circle"></i> CV (obligatoire)</li>
                    <li><i class="fas fa-check-circle"></i> Lettre de motivation (obligatoire)</li>
                    <li><i class="fas fa-check-circle"></i> Relevé de notes (recommandé)</li>
                </ul>
            </div>
            
            <div class="form-group">
                <label for="cvFile">CV (PDF uniquement)</label>
                <div class="file-upload" id="cvUploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Glissez-déposez votre CV ici ou cliquez pour sélectionner</p>
                    <input type="file" id="cvFile" name="cv_file" accept=".pdf" required style="display: none;">
                </div>
                <p id="cvFileName"></p>
            </div>
            
            <div class="form-group">
                <label for="motivationFile">Lettre de motivation (PDF uniquement)</label>
                <div class="file-upload" id="motivationUploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Glissez-déposez votre lettre ici ou cliquez pour sélectionner</p>
                    <input type="file" id="motivationFile" name="motivation_file" accept=".pdf" required style="display: none;">
                </div>
                <p id="motivationFileName"></p>
            </div>
            
            <div class="form-group">
                <label for="notesFile">Relevé de notes (optionnel)</label>
                <div class="file-upload" id="notesUploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Glissez-déposez votre relevé ici ou cliquez pour sélectionner</p>
                    <input type="file" id="notesFile" name="notes_file" accept=".pdf" style="display: none;">
                </div>
                <p id="notesFileName"></p>
            </div>
            
            <div class="form-group">
                <label for="messageCandidature">Message complémentaire (optionnel)</label>
                <textarea id="messageCandidature" name="message" class="form-control" rows="3" placeholder="Ajoutez un message pour l'entreprise..."></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="action-btn delete-btn" onclick="closePostulerModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="postuler-btn">
                    <i class="fas fa-paper-plane"></i> Envoyer la candidature
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Gestion du modal de postulation
function openPostulerModal(offreId) {
    document.getElementById('offreId').value = offreId;
    document.getElementById('postulerModal').style.display = 'block';
}

function closePostulerModal() {
    document.getElementById('postulerModal').style.display = 'none';
    document.getElementById('postulerForm').reset();
    document.getElementById('cvFileName').textContent = '';
    document.getElementById('motivationFileName').textContent = '';
    document.getElementById('notesFileName').textContent = '';
}

// Gestion du drag and drop
const setupFileUpload = (uploadAreaId, fileInputId, fileNameId) => {
    const uploadArea = document.getElementById(uploadAreaId);
    const fileInput = document.getElementById(fileInputId);
    
    uploadArea.addEventListener('click', () => fileInput.click());
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            updateFileName(fileNameId, fileInput);
        }
    });
    fileInput.addEventListener('change', () => updateFileName(fileNameId, fileInput));
}

// Initialisation des zones de dépôt
setupFileUpload('cvUploadArea', 'cvFile', 'cvFileName');
setupFileUpload('motivationUploadArea', 'motivationFile', 'motivationFileName');
setupFileUpload('notesUploadArea', 'notesFile', 'notesFileName');

function updateFileName(elementId, input) {
    const fileNameElement = document.getElementById(elementId);
    fileNameElement.textContent = input.files.length > 0 
        ? `Fichier sélectionné: ${input.files[0].name}` 
        : '';
}

// Fermer le modal si clic en dehors
window.onclick = function(event) {
    if (event.target === document.getElementById('postulerModal')) {
        closePostulerModal();
    }
}
</script>
</body>
</html>