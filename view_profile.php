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
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/page2.css">
    <style>
       <style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #4CAF50;
        --accent-color: #3498db;
        --light-gray: #f8f9fa;
        --text-color: #2c3e50;
    }

   

    .container {
        max-width: 800px;
        margin: 40px auto;
        padding: 40px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        transition: transform 0.3s ease;
    }

    .container:hover {
        transform: translateY(-2px);
    }

    h1 {
            color:rgb(241, 244, 248);
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
          h2 {
            color:rgb(60, 65, 71);
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
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
   
    .profile-details {
        margin: 30px 0;
    }

    .detail-row {
        display: flex;
        align-items: baseline;
        padding: 18px 20px;
        margin-bottom: 12px;
        background: var(--light-gray);
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .detail-row:hover {
        transform: translateX(10px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .detail-row strong {
        flex: 0 0 200px;
        font-weight: 500;
        color: var(--accent-color);
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-row span {
        flex: 1;
        font-size: 1rem;
        color: var(--text-color);
        word-break: break-word;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        padding: 12px 25px;
        background: var(--secondary-color);
        color: white;
        text-decoration: none;
        border-radius: 6px;
        margin-top: 25px;
        transition: all 0.3s ease;
        font-weight: 500;
        gap: 8px;
    }

    .back-btn::before {
        content: '←';
        font-size: 1.1em;
    }

    

    @media (max-width: 768px) {
        .container {
            margin: 20px;
            padding: 25px;
        }

        .detail-row {
            flex-direction: column;
            gap: 8px;
        }

        .detail-row strong {
            flex: none;
            width: 100%;
        }
    }
</style>
    </style>
</head>
<body>
           <header>
    <div class="header-info">
        
            <h1>Profil </h1>
            
    
    </div>
    <a href="admin.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</header>
    <div class="container">
        <h2>Détails du profil <?= htmlspecialchars($type) ?></h2>
        
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