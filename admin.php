<?php
session_start();

// Vérification des droits admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include('database.php');
$db = new Database();
$conn = $db->connect();

// Vérification admin
$stmt = $conn->prepare("SELECT id_admin FROM admin WHERE id_admin = :id");
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    header("Location: dashboard.php");
    exit();
}

// Récupération des données pour validation
$query_etudiants = "SELECT id_etudiant, nom, prenom, email, date_inscription 
                    FROM etudiants 
                    WHERE est_valide = 0";
$etudiants = $conn->query($query_etudiants)->fetchAll(PDO::FETCH_ASSOC);

$query_entreprises = "SELECT id_entreprise, nom, email, telephone, date_inscription
                      FROM entreprises 
                      WHERE est_valide = 0";
$entreprises = $conn->query($query_entreprises)->fetchAll(PDO::FETCH_ASSOC);

$query_offres = "SELECT o.id_offre, o.titre, e.nom AS entreprise, o.date_publication, o.date_debut, o.date_fin 
                 FROM offres_stage o 
                 JOIN entreprises e ON o.id_entreprise = e.id_entreprise 
                 WHERE o.est_valide = 0";
$offres = $conn->query($query_offres)->fetchAll(PDO::FETCH_ASSOC);

// Messages session
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validations - Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
      
    <style>
        :root {
            --primary: #0b4126;
            --secondary: #ff4c38;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background: #1e3a5f;
            color: white;
            padding: 20px 0;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu li a:hover, 
        .sidebar-menu li a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu li a i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .header h1 {
            color: var(--primary);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Admin Container */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .admin-section {
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .admin-section h2 {
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background: #f9f9f9;
            color: #555;
            font-weight: 500;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .badge-approved {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        /* Buttons */
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin: 2px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .approve-btn {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .approve-btn:hover {
            background-color: #c3e6cb;
        }
        
        .reject-btn {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .reject-btn:hover {
            background-color: #f5c6cb;
        }
        
        .view-btn {
            background-color: #E2E3E5;
            color: #383D41;
        }
        
        .view-btn:hover {
            background-color: #d6d8db;
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-user-shield"></i> Administration</h3>
        </div>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="admin.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="admin_offres.php" class="active"><i class="fas fa-check-circle"></i> Offres</a></li>
                <li><a href="admin_utilisateurs.php" class="active"><i class="fas fa-check-circle"></i> Utilisateurs</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Validations en attente</h1>
            <div class="user-menu">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>

        <div class="admin-container">
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Comptes étudiants à valider -->
            <div class="admin-section">
                <h2><i class="fas fa-user-graduate"></i> Comptes étudiants</h2>
                <?php if (count($etudiants) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Date inscription</th>
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
                                    <td><span class="badge badge-pending">En attente</span></td>
                                    <td>
                                        <form action="validate_account.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="id_utilisateur" value="<?= $etudiant['id_etudiant'] ?>">
                                            <input type="hidden" name="type" value="etudiant">
                                            <button type="submit" name="action" value="approve" class="action-btn approve-btn">
                                                <i class="fas fa-check"></i> Valider
                                            </button>
                                            <button type="submit" name="action" value="reject" class="action-btn reject-btn">
                                                <i class="fas fa-times"></i> Rejeter
                                            </button>
                                        </form>
                                        <a href="view_profile.php?id=<?= $etudiant['id_etudiant'] ?>&type=etudiant" class="action-btn view-btn">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucun compte étudiant en attente de validation.</p>
                <?php endif; ?>
            </div>

            <!-- Comptes entreprises à valider -->
            <div class="admin-section">
                <h2><i class="fas fa-building"></i> Comptes entreprises</h2>
                <?php if (count($entreprises) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entreprises as $entreprise): ?>
                                <tr>
                                    <td><?= htmlspecialchars($entreprise['nom']) ?></td>
                                    <td><?= htmlspecialchars($entreprise['email']) ?></td>
                                    <td><?= htmlspecialchars($entreprise['telephone']) ?></td>
                                    
                                    <td><span class="badge badge-pending">En attente</span></td>
                                    <td>
                                        <form action="validate_account.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="id_utilisateur" value="<?= $entreprise['id_entreprise'] ?>">
                                            <input type="hidden" name="type" value="entreprise">
                                            <button type="submit" name="action" value="approve" class="action-btn approve-btn">
                                                <i class="fas fa-check"></i> Valider
                                            </button>
                                            <button type="submit" name="action" value="reject" class="action-btn reject-btn">
                                                <i class="fas fa-times"></i> Rejeter
                                            </button>
                                        </form>
                                        <a href="view_profile.php?id=<?= $entreprise['id_entreprise'] ?>&type=entreprise" class="action-btn view-btn">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucun compte entreprise en attente de validation.</p>
                <?php endif; ?>
            </div>

            <!-- Offres de stage à valider -->
            <div class="admin-section">
                <h2><i class="fas fa-briefcase"></i> Offres de stage</h2>
                <?php if (count($offres) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Entreprise</th>
                                
                                <th>Période</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($offres as $offre): ?>
                                <tr>
                                    <td><?= htmlspecialchars($offre['titre']) ?></td>
                                    <td><?= htmlspecialchars($offre['entreprise']) ?></td>
                                    
                                    <td><?= date('d/m/Y', strtotime($offre['date_debut'])) ?> au <?= date('d/m/Y', strtotime($offre['date_fin'])) ?></td>
                                    <td><span class="badge badge-pending">En attente</span></td>
                                    <td>
                                        <form action="validate_offer.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="id_offre" value="<?= $offre['id_offre'] ?>">
                                            <button type="submit" name="action" value="approve" class="action-btn approve-btn">
                                                <i class="fas fa-check"></i> Valider
                                            </button>
                                            <button type="submit" name="action" value="reject" class="action-btn reject-btn">
                                                <i class="fas fa-times"></i> Rejeter
                                            </button>
                                        </form>
                                        <a href="view_offer.php?id=<?= $offre['id_offre'] ?>" class="action-btn view-btn">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucune offre de stage en attente de validation.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Confirmation pour les actions de rejet
        const rejectButtons = document.querySelectorAll('.reject-btn');
        rejectButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir rejeter cet élément ?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Animation pour les boutons
        const actionButtons = document.querySelectorAll('.action-btn');
        actionButtons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            });
            button.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });
    });
    </script>
</body>
</html>