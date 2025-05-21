<?php
session_start();

include('database.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// Récupérer les entreprises où l'étudiant a postulé
$query = "SELECT DISTINCT e.id_entreprise, e.nom
          FROM entreprises e
          JOIN offres_stage o ON e.id_entreprise = o.id_entreprise
          JOIN candidatures c ON o.id_offre = c.id_offre
          WHERE c.id_etudiant = :id_etudiant";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id_etudiant', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Évaluer une entreprise</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/page2.css">
          <style>
/* Style général */
body {
    background-color: #f5f7fb;
    font-family: 'Inter', sans-serif;
}

.dashboard-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
}
.rating-stars .star:hover,
.rating-stars .star:hover ~ .star,
.rating-stars input:checked ~ .star {
    color: #ffd700;
}

.rating-stars input {
    display: none;
}

/* Textarea */
textarea {
    width: 100%;
    height: 120px;
    padding: 1rem;
    margin: 1rem 0;
    border: 2px solid #e8eef3;
    border-radius: 8px;
    font-family: 'Roboto', sans-serif;
    font-size: 1rem;
    resize: vertical;
    transition: border-color 0.3s ease;
}
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
h2 {
    color: #2c3e50;
    font-size: 2rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #3498db;
}

.fa-comments {
    color: #3498db;
    font-size: 1.8rem;
}

/* Carte avis */
.avis-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.avis-card {
    padding: 1.5rem;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e8eef3;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
}

.avis-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.12);
}

h3 {
    color: #2c3e50;
    font-size: 1.4rem;
    margin-bottom: 0.5rem;
}

.note {
    color: #f1c40f;
    font-size: 1.4rem;
    margin: 0.5rem 0;
}

p {
    color: #34495e;
    line-height: 1.6;
    margin: 1rem 0;
    white-space: pre-wrap;
}

small {
    color: #7f8c8d;
    font-size: 0.9rem;
    display: block;
    margin-top: 1rem;
}

/* Actions */
.actions {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.9rem;
}

.btn-sm {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    border: none;
}

.btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.btn-danger {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
}

.btn-danger:hover {
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

/* Alert */
.alert-info {
    background: #f0f8ff;
    color: #2c3e50;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #3498db;
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        margin: 1rem;
        padding: 1.5rem;
    }
    
    h2 {
        font-size: 1.6rem;
    }
    
    .btn-sm {
        width: 100%;
        justify-content: center;
    }
    
    .actions {
        gap: 0.5rem;
    }
}
</style>
    
</head>
<body>
           <header>
    <div class="header-info">
        
            <h1>Évaluation des Entreprises</h1>
            
    
    </div>
    <a href="dashboard.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</header>
    <div class="dashboard-container">
        <h2>Évaluer une entreprise</h2>

        <?php if (empty($entreprises)): ?>
            <div class="alert alert-info">Vous devez avoir postulé à une offre pour évaluer une entreprise</div>
        <?php else: ?>
            <div class="entreprise-list">
                <?php foreach ($entreprises as $entreprise): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($entreprise['nom']) ?></h3>
                    <form class="review-form" method="POST" action="sauvegarder_avis.php">
                        <input type="hidden" name="id_entreprise" value="<?= $entreprise['id_entreprise'] ?>">
                        <div class="rating-stars">
                           <?php for ($i = 1; $i <= 5; $i++): ?>
<label class="star">
    <input type="radio" name="note" value="<?= $i ?>" required>
    <span class="star-icon">★</span>
</label>
<?php endfor; ?>
                        </div>
                        <textarea name="commentaire" placeholder="Votre expérience avec cette entreprise..."></textarea>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer l'avis
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

   <script>
document.querySelectorAll('.rating-stars').forEach(container => {
    container.addEventListener('mouseleave', () => {
        const checked = container.querySelector('input:checked');
        container.querySelectorAll('.star-icon').forEach(star => {
            star.style.color = checked && star.previousElementSibling.value <= checked.value 
                ? '#ffd700' 
                : '#ddd';
        });
    });
});
</script>
</body>
</html>
