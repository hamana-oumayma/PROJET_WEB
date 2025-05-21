<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include('database.php');
$db = new Database();
$conn = $db->connect();


// Récupérer l'ID de l'avis depuis l'URL
$id_avis = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_avis) {
    die("ID d'avis invalide");
}

// Récupérer l'avis existant
$query = "SELECT a.*, e.nom AS entreprise_nom 
          FROM avis_entreprises a
          JOIN entreprises e ON a.id_entreprise = e.id_entreprise
          WHERE a.id_avis = :id_avis 
          AND a.id_etudiant = :id_etudiant";

$stmt = $conn->prepare($query);
$stmt->execute([
    ':id_avis' => $id_avis,
    ':id_etudiant' => $_SESSION['user_id']
]);

$avis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$avis) {
    die("Avis non trouvé ou vous n'avez pas la permission de le modifier");
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = filter_input(INPUT_POST, 'note', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);
    
    $commentaire = filter_input(INPUT_POST, 'commentaire', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    
    if (!$note || strlen($commentaire) > 500) {
        $error = "Données invalides";
    } else {
        $updateQuery = "UPDATE avis_entreprises 
                       SET note = :note, 
                           commentaire = :commentaire,
                           date_avis = NOW()
                       WHERE id_avis = :id_avis
                       AND id_etudiant = :id_etudiant";
        
        $stmt = $conn->prepare($updateQuery);
        $success = $stmt->execute([
            ':note' => $note,
            ':commentaire' => $commentaire,
            ':id_avis' => $id_avis,
            ':id_etudiant' => $_SESSION['user_id']
        ]);
        
        if ($success) {
            $_SESSION['success'] = "Avis mis à jour avec succès!";
            header("Location: mes_avis.php");
            exit();
        } else {
            $error = "Erreur lors de la mise à jour";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier l'avis</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
/* Container principal */
.dashboard-container {
    max-width: 700px;
    margin: 2rem auto;
    padding: 2.5rem;
    background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 18px;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    transition: transform 0.3s ease;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.dashboard-container:hover {
    transform: translateY(-2px);
}

/* Titre */
.dashboard-container h2 {
    font-size: 2rem;
    color: #2d3436;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 1rem;
    border-bottom: 2px solidrgb(17, 49, 83);
}

.dashboard-container h2 i {
    color:rgb(15, 41, 68);
    font-size: 1.8rem;
}

/* Étoiles de notation */
.rating-stars {
    display: inline-flex;
    gap: 0.5rem;
    font-size: 2.5rem;
    padding: 0.5rem;
    border-radius: 12px;
    background: rgba(0, 123, 255, 0.05);
}

.star {
    cursor: pointer;
    color: #e0e0e0;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.star:hover {
    transform: scale(1.15);
    color: #ffd700;
}

.star.selected {
    color: #ffb400;
    animation: starPop 0.3s ease;
}

@keyframes starPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Zone de texte */
textarea {
    width: 100%;
    min-height: 150px;
    padding: 1rem 1.25rem;
    font-size: 1rem;
    line-height: 1.6;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
    resize: vertical;
    backdrop-filter: blur(4px);
}

textarea:focus {
    border-color:rgb(19, 46, 75);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
    background: white;
}

/* Boutons */
.form-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn-primary, .btn-secondary {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
     background-color: #fef3c7;
    color: #92400e;
    
    border: none;
    box-shadow: 0 4px 6px rgba(0, 123, 255, 0.2);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(0, 123, 255, 0.3);
}

.btn-secondary {
    background: linear-gradient(135deg,rgb(219, 221, 224) 0%,rgb(214, 218, 223) 100%);
    color:rgb(0, 0, 0);
    border: 1px solid #dee2e6;
}

.btn-secondary:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Compteur de caractères */
.char-counter {
    font-size: 0.85rem;
    color: #6c757d;
    text-align: right;
    margin-top: 0.5rem;
}

/* Animation chargement */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.loading {
    animation: pulse 1.5s infinite;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 1.5rem;
        margin: 1rem;
    }
    
    .rating-stars {
        font-size: 2rem;
    }
    
    textarea {
        min-height: 120px;
    }
}
</style>
</head>
<body>
    
   
    <div class="dashboard-container">
        <h2><i class="fas fa-edit"></i> Modifier votre avis sur <?= htmlspecialchars($avis['entreprise_nom']) ?></h2>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="rating-container">
                <label>Note :</label>
                <div class="rating-stars">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                        <label class="star <?= $i <= $avis['note'] ? 'selected' : '' ?>">
                            <input type="radio" name="note" value="<?= $i ?>" 
                                <?= $i == $avis['note'] ? 'checked' : '' ?> required>
                            ★
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Commentaire :</label>
                <textarea name="commentaire" 
                          maxlength="500" 
                          placeholder="Décrivez votre expérience..."><?= 
                          htmlspecialchars($avis['commentaire']) ?></textarea>
                <small>500 caractères maximum</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="mes_avis.php" class="btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>

    <script>
        // Script pour les étoiles interactives
        document.querySelectorAll('.star input').forEach(star => {
            star.addEventListener('change', function() {
                const stars = this.parentNode.parentNode.children;
                Array.from(stars).forEach(s => {
                    s.classList.remove('selected');
                    if (s.querySelector('input').value <= this.value) {
                        s.classList.add('selected');
                    }
                });
            });
        });
    </script>
</body>
</html>