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

    // Récupération des données
    $role = $_POST['role'] ?? '';
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

    // Vérification si email existe déjà (dans les deux tables)
    $stmt = $pdo->prepare("(SELECT email FROM etudiants WHERE email = ?) UNION (SELECT email FROM entreprises WHERE email = ?)");
    $stmt->execute([$email, $email]);
    if ($stmt->fetch()) {
        die("⚠️ Un compte avec cet e-mail existe déjà.");
    }

    if ($role === 'etudiant') {
        $nom = $_POST['nom_etudiant'] ?? '';
        $prenom = $_POST['prenom'] ?? '';

        // Ajout du champ est_valide = 0
        $sql = "INSERT INTO etudiants (nom, prenom, email, mdp, est_valide) VALUES (?, ?, ?, ?, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nom, $prenom, $email, $password]);

    } elseif ($role === 'entreprise') {
        $nom = $_POST['nom_entreprise'] ?? '';
        $adresse = $_POST['adresse'] ?? '';
        $telephone = $_POST['telephone'] ?? '';

        // Ajout du champ est_valide = 0
        $sql = "INSERT INTO entreprises (nom, adresse, email, telephone, mdp, est_valide) VALUES (?, ?, ?, ?, ?, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nom, $adresse, $email, $telephone, $password]);
    }

    // Message de confirmation
    echo '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Inscription en attente</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <meta http-equiv="refresh" content="5;url=login.html">
        <style>
            body { height: 100vh; display: flex; justify-content: center; align-items: center; background-color: #f8f9fa; }
        </style>
    </head>
    <body>
        <div class="alert alert-info text-center" style="max-width: 500px;">
            <h4>Inscription soumise avec succès !</h4>
            <p>Votre compte est en attente de validation par l\'administrateur.</p>
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </body>
    </html>';
    exit();
}
?>
