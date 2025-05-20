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
$query = "SELECT nom, prenom, email FROM etudiants WHERE id_etudiant = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$etudiant) {
    die("Étudiant non trouvé");
}

// Générer les initiales
$initials = '';
if (!empty($etudiant['prenom']) && !empty($etudiant['nom'])) {
    $initials = strtoupper(substr($etudiant['prenom'], 0, 1)) . strtoupper(substr($etudiant['nom'], 0, 1));
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';

    $updateQuery = "UPDATE etudiants SET 
                    nom = :nom,
                    prenom = :prenom,
                    email = :email
                    WHERE id_etudiant = :id";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':prenom', $prenom);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Profil mis à jour avec succès!";
        header("Location: profil.php"); // Corrected path
        exit();
    } else {
        $error = "Erreur lors de la mise à jour du profil";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier mon profil</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        
       .edit-btn {
    background-color:rgb(37, 53, 88);
    color: white;
    padding: 8px 16px; 
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px; 
    min-width: 120px; 
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin: 0 auto; 
    display: block; 
    width: auto; 
    margin-right: 5px;
}


.edit-btn.compact {
    min-width: 90px;
    padding: 6px 12px;
    font-size: 13px;
}


.edit-btn:active {
    transform: scale(0.95);
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
<header>
    <div class="header-info">
        <div class="user-avatar"><?= $initials ?></div>
        <div>
            <h1>Mon Profil</h1>
            <h4>Bonjour, <?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?> !</h4>
        </div>
    </div>
    <div class="header-actions">
        
        <a href="profil.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> retour
        </a>
    </div>
</header>

<div class="dashboard-container">
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="card">
        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($etudiant['nom']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($etudiant['prenom']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($etudiant['email']) ?>" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="edit-btn">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </div>
    </form>
</div>
</body>
</html>