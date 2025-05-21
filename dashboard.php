<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

include('database.php');
$db = new Database();
$conn = $db->connect();

// Infos étudiant
$stmt_info = $conn->prepare("SELECT nom, prenom FROM etudiants WHERE id_etudiant = :id");
$stmt_info->bindParam(':id', $user_id);
$stmt_info->execute();
$etudiant = $stmt_info->fetch(PDO::FETCH_ASSOC);

$prenom = $etudiant['prenom'] ?? '';
$nom = $etudiant['nom'] ?? '';
$user_full_name = trim($prenom . ' ' . $nom);

$firstInitial = isset($prenom[0]) ? $prenom[0] : '';
$secondInitial = isset($nom[0]) ? $nom[0] : '';
$initials = strtoupper($firstInitial . $secondInitial);

// Récupération des candidatures
$query = "SELECT c.id_candidature, o.titre AS poste, o.date_debut, o.date_fin, c.statut, e.nom AS entreprise
          FROM candidatures c
          JOIN offres_stage o ON c.id_offre = o.id_offre
          JOIN entreprises e ON o.id_entreprise = e.id_entreprise
          WHERE c.id_etudiant = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Offres recommandées
$query_offres = "SELECT o.id_offre, o.titre AS poste, o.date_debut, o.date_fin, e.nom AS entreprise
                 FROM offres_stage o
                 JOIN entreprises e ON o.id_entreprise = e.id_entreprise
                 WHERE o.id_offre NOT IN 
                   (SELECT id_offre FROM candidatures WHERE id_etudiant = :user_id)
                 ORDER BY o.date_publication DESC
                 LIMIT 5";
$stmt_offres = $conn->prepare($query_offres);
$stmt_offres->bindParam(':user_id', $user_id);
$stmt_offres->execute();
$offres = $stmt_offres->fetchAll(PDO::FETCH_ASSOC);

// Documents
$query_documents = "SELECT d.id_document, o.titre AS offre, d.type_document, d.chemin_fichier, d.id_candidature
                    FROM documents d
                    JOIN candidatures c ON d.id_candidature = c.id_candidature
                    JOIN offres_stage o ON c.id_offre = o.id_offre
                    WHERE c.id_etudiant = :user_id
                    ORDER BY d.id_document DESC";
$stmt_documents = $conn->prepare($query_documents);
$stmt_documents->bindParam(':user_id', $user_id);
$stmt_documents->execute();
$documents = $stmt_documents->fetchAll(PDO::FETCH_ASSOC);

// Messages session
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

if (isset($_GET['download'])) {
    require_once 'download_document.php';
    exit;
}

// 1. Statistiques des candidatures
$query_stats = "SELECT 
    COUNT(*) AS total,
    SUM(statut = 'en_attente') AS en_attente,
    SUM(statut = 'accepte') AS accepte,
    SUM(statut = 'refuse') AS refuse
FROM candidatures 
WHERE id_etudiant = :user_id";

$stmt_stats = $conn->prepare($query_stats);
$stmt_stats->bindParam(':user_id', $user_id);
$stmt_stats->execute();
$stats_candidatures = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// 2. Documents manquants
$query_docs = "SELECT COUNT(*) AS documents_manquants 
               FROM documents 
               WHERE id_candidature IN (
                   SELECT id_candidature FROM candidatures WHERE id_etudiant = :user_id
               ) AND chemin_fichier IS NULL";

$stmt_docs = $conn->prepare($query_docs);
$stmt_docs->bindParam(':user_id', $user_id);
$stmt_docs->execute();
$docs_manquants = $stmt_docs->fetch(PDO::FETCH_ASSOC);

// 3. Dernières offres
$query_offres = "SELECT o.titre, e.nom AS entreprise, o.date_debut 
                FROM offres_stage o
                JOIN entreprises e ON o.id_entreprise = e.id_entreprise
                ORDER BY o.date_publication DESC 
                LIMIT 3";

$stmt_offres = $conn->prepare($query_offres);
$stmt_offres->execute();
$dernieres_offres = $stmt_offres->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Stages</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="assets/page2.css">
    <style>
        
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

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9em;
            margin: 2px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .upload-btn {  background-color: #d1fae5;
            color: #065f46; }
        .download-btn { background-color: #2196F3; color: white; }
        .view-btn { background-color:rgb(203, 199, 254); color:rgb(122, 40, 199); }
        .postuler-btn {  background-color: #fee2e2;
            color: #991b1b;}
        
        .required-documents {
            margin-bottom: 15px;
        }
        
        .required-documents ul {
            list-style-type: none;
            padding: 0;
        }
        
        .required-documents li {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .required-documents li i {
            margin-right: 8px;
            color: #4CAF50;
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
            background-color: var(--primary-color);
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

        nav ul li a:hover, nav ul li a.active {
            background-color: rgba(255,255,255,0.1);
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
        <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
        <div>
            <h1>Tableau de Bord</h1>
            <h4>Bienvenue, <?= htmlspecialchars($user_full_name) ?> !</h4>
        </div>
    </div>
    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Se déconnecter</a>
</header>

<nav>
    <ul>
        <li><a href="#" class="active"><i class="fas fa-home"></i> Tableau de bord</a></li>
        <li><a href="./model/stages.php"><i class="fas fa-briefcase"></i> Offres de stage</a></li>
        <li><a href="./model/candidatures.php"><i class="fas fa-users"></i> Mes candidatures</a></li>
        <li><a href="./model/documents.php"><i class="fas fa-file-contract"></i> Mes documents</a></li>
        <li><a href="./model/profil.php"><i class="fas fa-building"></i> Mon profil</a></li>
        
<li><a href="evaluer_entreprise.php"><i class="fas fa-star-half-alt"></i> Évaluer une entreprise</a></li>
<li><a href="mes_avis.php"><i class="fas fa-comment-dots"></i> Mes avis</a></li>

    </ul>
</nav>

<div class="dashboard-container">
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2><i class="fas fa-home"></i> Tableau de bord</h2>
        <p>Consultez vos candidatures, suivez vos stages et gérez vos documents.</p>
    </section>
<div class="dashboard-container">
    <div class="card">
    <h2><i class="fas fa-chart-bar"></i> Statistiques</h2>
    <div class="stats-grid">

        <div class="stat-card">
            <i class="fas fa-briefcase stat-icon"></i>
            <div class="stat-value"><?= $stats_candidatures['total'] ?></div>
            <div class="stat-label">Candidatures</div>
        </div>

        <div class="stat-card">
            <i class="fas fa-hourglass-half stat-icon"></i>
            <div class="stat-value"><?= $stats_candidatures['en_attente'] ?></div>
            <div class="stat-label">En attente</div>
        </div>

        <div class="stat-card">
            <i class="fas fa-check-circle stat-icon"></i>
            <div class="stat-value"><?= $stats_candidatures['accepte'] ?></div>
            <div class="stat-label">Acceptées</div>
        </div>

        <div class="stat-card">
            <i class="fas fa-times-circle stat-icon"></i>
            <div class="stat-value"><?= $stats_candidatures['refuse'] ?></div>
            <div class="stat-label">Refusées</div>
        </div>

        <div class="stat-card">
            <i class="fas fa-file-alt stat-icon"></i>
            <div class="stat-value"><?= $docs_manquants['documents_manquants'] ?></div>
            <div class="stat-label">Docs manquants</div>
        </div>

    </div>
</div>

        
    <!-- Candidatures -->
    <h3><i class="fas fa-clock"></i> Candidatures récentes</h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Entreprise</th>
                    <th>Poste</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($candidatures as $candidature): ?>
    <tr>
        <td><?= htmlspecialchars($candidature['entreprise']) ?></td>
        <td><?= htmlspecialchars($candidature['poste']) ?></td>
        <td><?= htmlspecialchars($candidature['date_debut']) ?></td>
        <td><?= htmlspecialchars($candidature['date_fin']) ?></td>
        <td>
            <span class="status-badge <?= strtolower(str_replace([' ', 'é'], ['_', 'e'], $candidature['statut'])) ?>">
                <?= ucfirst($candidature['statut']) ?>
            </span>
        </td>
        <td>
            <a href="./model/candidatures.php?id_candidature=<?= urlencode($candidature['id_candidature']) ?>" class="action-btn view-btn">
                <i class="fas fa-eye"></i> Voir
            </a>
        </td>
    </tr>
<?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <!-- Offres recommandées -->
    <h3><i class="fas fa-briefcase"></i> Offres recommandées</h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Entreprise</th>
                    <th>Poste</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($offres as $offre): ?>
                <tr>
                    <td><?= htmlspecialchars($offre['entreprise']) ?></td>
                    <td><?= htmlspecialchars($offre['poste']) ?></td>
                    <td><?= htmlspecialchars($offre['date_debut']) ?></td>
                    <td><?= htmlspecialchars($offre['date_fin']) ?></td>
                    <td>
                        <button class="action-btn postuler-btn" onclick="openPostulerModal(<?= $offre['id_offre'] ?>)">
                            <i class="fas fa-paper-plane"></i> Postuler
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Documents -->
    <h3><i class="fas fa-file-alt"></i> Mes documents</h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Offre</th>
                    <th>Type de document</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($documents as $document): ?>
                <tr>
                    <td><?= htmlspecialchars($document['offre']) ?></td>
                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $document['type_document']))) ?></td>
                    <td>
                        <span class="status-badge <?= $document['chemin_fichier'] ? 'completed' : 'pending' ?>">
                            <?= $document['chemin_fichier'] ? 'Complété' : 'Manquant' ?>
                        </span>
                    </td>
                    <td>
                        <a href="./model/documents.php?id_candidature=<?= urlencode($document['id_candidature']) ?>" class="action-btn view-btn">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                        <button class="action-btn upload-btn" onclick="openUploadModal(<?= $document['id_document'] ?>)">
                            <i class="fas fa-upload"></i> <?= empty($document['chemin_fichier']) ? 'Téléverser' : 'Remplacer' ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

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
                <button type="submit" class="action-btn postuler-btn">
                    <i class="fas fa-paper-plane"></i> Envoyer la candidature
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal d'upload -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-file-upload"></i> Téléverser un document</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form id="documentForm" enctype="multipart/form-data" method="POST" action="upload_document.php">
            <input type="hidden" id="documentId" name="id_document">
            
            <div class="form-group">
                <label for="documentType">Type de document</label>
                <select id="documentType" name="type_document" class="form-control" required>
                    <option value="">Sélectionnez un type</option>
                    <option value="cv">CV</option>
                    <option value="lettre_motivation">Lettre de motivation</option>
                    <option value="convention_stage">Convention de stage</option>
                    <option value="autre">Autre document</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Fichier</label>
                <div class="file-upload" id="fileUploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Glissez-déposez votre fichier ici ou cliquez pour sélectionner</p>
                    <input type="file" id="documentFile" name="document_file" required style="display: none;">
                </div>
                <p id="fileName"></p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="action-btn delete-btn" onclick="closeModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="action-btn upload-btn">
                    <i class="fas fa-upload"></i> Téléverser
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

// Gestion du modal d'upload
function openUploadModal(documentId) {
    document.getElementById('documentId').value = documentId;
    document.getElementById('uploadModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('uploadModal').style.display = 'none';
    document.getElementById('documentForm').reset();
    document.getElementById('fileName').textContent = '';
}

// Gestion du drag and drop pour le CV
const cvUploadArea = document.getElementById('cvUploadArea');
const cvFileInput = document.getElementById('cvFile');

cvUploadArea.addEventListener('click', () => cvFileInput.click());
cvUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    cvUploadArea.classList.add('dragover');
});
cvUploadArea.addEventListener('dragleave', () => {
    cvUploadArea.classList.remove('dragover');
});
cvUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    cvUploadArea.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        cvFileInput.files = e.dataTransfer.files;
        updateFileName('cvFileName', cvFileInput);
    }
});

cvFileInput.addEventListener('change', () => updateFileName('cvFileName', cvFileInput));

// Gestion du drag and drop pour la lettre de motivation
const motivationUploadArea = document.getElementById('motivationUploadArea');
const motivationFileInput = document.getElementById('motivationFile');

motivationUploadArea.addEventListener('click', () => motivationFileInput.click());
motivationUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    motivationUploadArea.classList.add('dragover');
});
motivationUploadArea.addEventListener('dragleave', () => {
    motivationUploadArea.classList.remove('dragover');
});
motivationUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    motivationUploadArea.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        motivationFileInput.files = e.dataTransfer.files;
        updateFileName('motivationFileName', motivationFileInput);
    }
});

motivationFileInput.addEventListener('change', () => updateFileName('motivationFileName', motivationFileInput));

// Gestion du drag and drop pour le relevé de notes
const notesUploadArea = document.getElementById('notesUploadArea');
const notesFileInput = document.getElementById('notesFile');

notesUploadArea.addEventListener('click', () => notesFileInput.click());
notesUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    notesUploadArea.classList.add('dragover');
});
notesUploadArea.addEventListener('dragleave', () => {
    notesUploadArea.classList.remove('dragover');
});
notesUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    notesUploadArea.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        notesFileInput.files = e.dataTransfer.files;
        updateFileName('notesFileName', notesFileInput);
    }
});

notesFileInput.addEventListener('change', () => updateFileName('notesFileName', notesFileInput));

// Gestion du drag and drop pour le modal d'upload
const fileUploadArea = document.getElementById('fileUploadArea');
const fileInput = document.getElementById('documentFile');

fileUploadArea.addEventListener('click', () => fileInput.click());
fileUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileUploadArea.classList.add('dragover');
});
fileUploadArea.addEventListener('dragleave', () => {
    fileUploadArea.classList.remove('dragover');
});
fileUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    fileUploadArea.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        updateFileName('fileName', fileInput);
    }
});

fileInput.addEventListener('change', () => updateFileName('fileName', fileInput));

function updateFileName(elementId, input) {
    const fileNameElement = document.getElementById(elementId);
    if (input.files.length > 0) {
        fileNameElement.textContent = `Fichier sélectionné: ${input.files[0].name}`;
    } else {
        fileNameElement.textContent = '';
    }
}

// Fermer les modals si on clique en dehors
window.addEventListener('click', (e) => {
    if (e.target === document.getElementById('postulerModal')) {
        closePostulerModal();
    }
    if (e.target === document.getElementById('uploadModal')) {
        closeModal();
    }
});
</script>
</body>
</html>