<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

include('../database.php');
$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];

// Récupérer les infos actuelles
$query = "SELECT nom, email, telephone, adresse FROM entreprises WHERE id_entreprise = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    die("Entreprise non trouvée");
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $adresse = $_POST['adresse'] ?? '';

    // Requête mise à jour avec seulement les colonnes existantes
    $updateQuery = "UPDATE entreprises SET 
                    nom = :nom,
                    email = :email,
                    telephone = :telephone,
                    adresse = :adresse
                    WHERE id_entreprise = :id";
    
    try {
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':adresse', $adresse);
        $stmt->bindParam(':id', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Profil mis à jour avec succès!";
            header("Location: profilE.php");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Erreur technique : " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le profil</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<header>
    <div class="header-info">
        <h1>Modifier le profil</h1>
    </div>
    <a href="profilE.php" class="logout-btn">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</header>

<div class="dashboard-container">
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="card">
    <div class="form-group">
        <label for="nom">Nom de l'entreprise</label>
        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($entreprise['nom']) ?>" required>
    </div>
    
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($entreprise['email']) ?>" required>
    </div>
    
    <div class="form-group">
        <label for="telephone">Téléphone</label>
        <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($entreprise['telephone'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label for="adresse">Adresse</label>
        <textarea id="adresse" name="adresse"><?= htmlspecialchars($entreprise['adresse'] ?? '') ?></textarea>
    </div>
    
     
    <div class="form-group">
    <button type="submit" class="action-btn" style="background-color: #4CAF50;">
            <i class="fas fa-save"></i> Enregistrer
        </button>
    </div>
</form>
</div>
</body>
</html>