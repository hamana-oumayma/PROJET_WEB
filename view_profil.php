<?php
session_start();

// Vérification des droits admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include('database.php');
$db = new Database();
$conn = $db->connect();

$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? null;

if (!$id || !$type) {
    header("Location: admin.php");
    exit();
}

try {
    if ($type === 'etudiant') {
        $stmt = $conn->prepare("SELECT * FROM etudiants WHERE id_etudiant = :id");
    } elseif ($type === 'entreprise') {
        $stmt = $conn->prepare("SELECT * FROM entreprises WHERE id_entreprise = :id");
    } else {
        header("Location: admin.php");
        exit();
    }
    
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        throw new Exception("Profil non trouvé");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du profil</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
         body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        
        .profile-details {
            margin: 20px 0;
        }
        
        .detail-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
        }
        
        .detail-row strong {
            width: 200px;
            color: #555;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Détails du profil <?= htmlspecialchars($type) ?></h1>
        
        <div class="profile-details">
            <?php foreach ($profile as $key => $value): ?>
                <?php if (!is_numeric($key) && $key !== 'mdp'): ?>
                    <div class="detail-row">
                        <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?>:</strong>
                        <span><?= htmlspecialchars($value) ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <a href="admin.php" class="back-btn">Retour à l'administration</a>
    </div>
</body>
</html>