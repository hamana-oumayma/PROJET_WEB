<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = 'localhost';
    $db = 'gestion_stages';
    $user = 'root';
    $pass = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }

    $role = $_POST['role'] ?? '';
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

    if ($role === 'etudiant') {
        $nom = $_POST['nom_etudiant'] ?? '';
        $prenom = $_POST['prenom'] ?? '';

        $sql = "INSERT INTO etudiants (nom, prenom, email, mdp) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nom, $prenom, $email, $password]);

    } elseif ($role === 'entreprise') {
        $nom = $_POST['nom_entreprise'] ?? '';
        $adresse = $_POST['adresse'] ?? '';
        $telephone = $_POST['telephone'] ?? '';

        $sql = "INSERT INTO entreprises (nom, adresse, email, telephone, mdp) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nom, $adresse, $email, $telephone, $password]);

    } else {
        die("Rôle invalide.");
    }

    // Alerte + redirection
    echo '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Inscription réussie</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <meta http-equiv="refresh" content="2;url=login.php">
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: #f8f9fa;
            }
        </style>
    </head>
    <body>
        <div class="alert alert-success alert-dismissible fade show text-center" role="alert" style="max-width: 500px;">
            ✅ Utilisateur créé avec succès ! Redirection vers la page de connexion...
        </div>
    </body>
    </html>';
    exit();
} else {
    echo "Le formulaire n'a pas été soumis correctement.";
}
