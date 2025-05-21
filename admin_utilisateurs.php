<?php
session_start();

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include('database.php');
$db = new Database();
$conn = $db->connect();

// Vérification du statut admin
$stmt = $conn->prepare("SELECT id_admin FROM admin WHERE id_admin = :id");
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    header("Location: dashboard.php");
    exit();
}

// Récupération séparée des données
$etudiants = $conn->query("
    SELECT id_etudiant AS id, nom, prenom, email, date_inscription, est_valide 
    FROM etudiants 
    ORDER BY date_inscription DESC
")->fetchAll(PDO::FETCH_ASSOC);

$entreprises = $conn->query("
    SELECT id_entreprise AS id, nom, email, date_inscription, est_valide 
    FROM entreprises 
    ORDER BY date_inscription DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $action = $_POST['action'];
    
    try {
        $table = ($type === 'etudiant') ? 'etudiants' : 'entreprises';
        $id_column = ($type === 'etudiant') ? 'id_etudiant' : 'id_entreprise';
        
        if ($action === 'toggle_activation') {
            $stmt = $conn->prepare("UPDATE $table SET est_valide = NOT est_valide WHERE $id_column = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $_SESSION['message'] = "Statut modifié avec succès";
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM $table WHERE $id_column = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $_SESSION['message'] = "Utilisateur supprimé";
        }
        
        header("Location: admin_utilisateurs.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
    }
}

// Gestion des messages
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/page2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta charset="UTF-8">
    <title>Administration</title>
    <style>
        /* Styles des onglets */
        .tab-container {
            margin: 20px 0;
        }
        .tabs button {
            padding: 12px 24px;
            border: none;
            background: #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tabs button.active {
            background: #1e3a5f;
            color: white;
        }
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        .tab-content.active {
            display: block;
        }

        /* Styles communs */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            cursor: pointer;
            border-radius: 3px;
            background-color: #d1fae5 ;
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
    </style>
</head>
<body>
        <header>
    <div class="header-info">
        
            <h1>Gestion des utilisateurs</h1>

    
    </div>
    <a href="admin.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</header>
 <div class="card">
       
    <div class="container">
       

        <div class="tab-container">
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab(event, 'etudiants')">Étudiants</button>
                <button class="tab-btn" onclick="openTab(event, 'entreprises')">Entreprises</button>
            </div>

            <!-- Onglet Étudiants -->
            <div id="etudiants" class="tab-content active">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Inscription</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etudiants as $etudiant): ?>
                        <tr>
                            <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                            <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                            <td><?= htmlspecialchars($etudiant['email']) ?></td>
                            <td><?= date('d/m/Y', strtotime($etudiant['date_inscription'])) ?></td>
                            <td>
                                <span class="badge <?= $etudiant['est_valide'] ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $etudiant['est_valide'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?= $etudiant['id'] ?>">
                                    <input type="hidden" name="type" value="etudiant">
                                      <input type="hidden" name="action" value="toggle_activation">
        <button type="submit" class="action-btn" title="Activer/Désactiver" style="color: #28a745;">
            <i class="fas fa-check-circle"></i>
        </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer la suppression ?')">
                                    <input type="hidden" name="id" value="<?= $etudiant['id'] ?>">
                                    <input type="hidden" name="type" value="etudiant">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="action-btn" style="background:  #fee2e2 ; color: red" title="Supprimer">
                                        ✖
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Onglet Entreprises -->
            <div id="entreprises" class="tab-content">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Inscription</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entreprises as $entreprise): ?>
                        <tr>
                            <td><?= htmlspecialchars($entreprise['nom']) ?></td>
                            <td><?= htmlspecialchars($entreprise['email']) ?></td>
                            <td><?= date('d/m/Y', strtotime($entreprise['date_inscription'])) ?></td>
                            <td>
                                <span class="badge <?= $entreprise['est_valide'] ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $entreprise['est_valide'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?= $etudiant['id'] ?>">
                                    <input type="hidden" name="type" value="etudiant">
                                      <input type="hidden" name="action" value="toggle_activation">
        <button type="submit" class="action-btn" title="Activer/Désactiver" style="color: #28a745;">
            <i class="fas fa-check-circle"></i>
        </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer la suppression ?')">
                                    <input type="hidden" name="id" value="<?= $etudiant['id'] ?>">
                                    <input type="hidden" name="type" value="etudiant">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="action-btn" style="background:  #fee2e2 ; color: red" title="Supprimer">
                                        ✖
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Gestion des onglets
    function openTab(evt, tabName) {
        // Masquer tous les onglets
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Désactiver tous les boutons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Afficher l'onglet sélectionné
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
    }

    // Message automatique
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);
    </script>
</body>
</html>