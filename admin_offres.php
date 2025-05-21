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

// Récupération de toutes les offres
$offres = $conn->query("
    SELECT o.*, e.nom AS entreprise, e.email AS email_entreprise
    FROM offres_stage o
    JOIN entreprises e ON o.id_entreprise = e.id_entreprise
    ORDER BY o.date_publication DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Messages session
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

// Actions sur les offres
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_offre = $_POST['id_offre'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'toggle_activation') {
            $stmt = $conn->prepare("UPDATE offres_stage SET est_valide = NOT est_valide WHERE id_offre = :id");
            $stmt->bindParam(':id', $id_offre);
            $stmt->execute();
            
            $_SESSION['message'] = "Statut de l'offre modifié avec succès";
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM offres_stage WHERE id_offre = :id");
            $stmt->bindParam(':id', $id_offre);
            $stmt->execute();
            
            $_SESSION['message'] = "Offre supprimée avec succès";
        }
        
        header("Location: admin_offres.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'opération: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Offres - Administration</title>
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
            background:  #1e3a5f;
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
        
        .badge-success {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .badge-warning {
            background-color: #FFF3CD;
            color: #856404;
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
        
        .toggle-btn {
            background-color: #E2E3E5;
            color: #383D41;
        }
        
        .toggle-btn:hover {
            background-color: #d6d8db;
        }
        
        .delete-btn {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .delete-btn:hover {
            background-color: #f5c6cb;
        }
        
        .view-btn {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .view-btn:hover {
            background-color: #bee5eb;
        }
        
        .edit-btn {
            background-color: #E2E3E5;
            color: #383D41;
        }
        
        .edit-btn:hover {
            background-color: #d6d8db;
        }
        
        /* Search and Filter */
        .search-filter {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }
        
        .search-box {
            flex-grow: 1;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            background: white;
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
            
            .search-filter {
                flex-direction: column;
            }
            
            table {
                display: block;
                overflow-x: auto;
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
                <li><a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="admin.php"><i class="fas fa-check-circle"></i> Validations</a></li>
                <li><a href="admin_utilisateurs.php"><i class="fas fa-users-cog"></i> Utilisateurs</a></li>
                <li><a href="admin_offres.php" class="active"><i class="fas fa-briefcase"></i> Offres</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Gestion des Offres de Stage</h1>
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

            <div class="admin-section">
                <h2><i class="fas fa-briefcase"></i> Liste des offres</h2>
                
                <div class="search-filter">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher une offre...">
                    </div>
                    <select class="filter-select" id="filterSelect">
                        <option value="all">Toutes les offres</option>
                        <option value="active">Offres actives</option>
                        <option value="inactive">Offres désactivées</option>
                    </select>
                </div>
                
                <div class="table-responsive">
                    <table id="offersTable">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Entreprise</th>
                                <th>Date publication</th>
                                <th>Période</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($offres as $offre): ?>
                                <tr data-status="<?= $offre['est_valide'] ? 'active' : 'inactive' ?>">
                                    <td><?= htmlspecialchars($offre['titre']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($offre['entreprise']) ?>
                                        <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($offre['email_entreprise']) ?></div>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($offre['date_publication'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($offre['date_debut'])) ?>
                                        <br>au
                                        <?= date('d/m/Y', strtotime($offre['date_fin'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $offre['est_valide'] ? 'badge-success' : 'badge-warning' ?>">
                                            <?= $offre['est_valide'] ? 'Active' : 'Désactivée' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="id_offre" value="<?= $offre['id_offre'] ?>">
                                            <input type="hidden" name="action" value="toggle_activation">
                                            <button type="submit" class="action-btn toggle-btn" title="<?= $offre['est_valide'] ? 'Désactiver' : 'Activer' ?>">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        <a href="view_offer.php?id=<?= $offre['id_offre'] ?>" class="action-btn view-btn" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_offer.php?id=<?= $offre['id_offre'] ?>" class="action-btn edit-btn" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette offre ?');">
                                            <input type="hidden" name="id_offre" value="<?= $offre['id_offre'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="action-btn delete-btn" title="Supprimer">
                                                <i class="fas fa-trash"></i>
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
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fonctionnalité de recherche
        const searchInput = document.getElementById('searchInput');
        const filterSelect = document.getElementById('filterSelect');
        const tableRows = document.querySelectorAll('#offersTable tbody tr');
        
        function filterOffers() {
            const searchTerm = searchInput.value.toLowerCase();
            const filterValue = filterSelect.value;
            
            tableRows.forEach(row => {
                const status = row.getAttribute('data-status');
                const textContent = row.textContent.toLowerCase();
                
                const matchesSearch = textContent.includes(searchTerm);
                const matchesFilter = 
                    filterValue === 'all' ||
                    (filterValue === 'active' && status === 'active') ||
                    (filterValue === 'inactive' && status === 'inactive');
                
                if (matchesSearch && matchesFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        searchInput.addEventListener('input', filterOffers);
        filterSelect.addEventListener('change', filterOffers);
        
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