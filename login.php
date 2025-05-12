<?php
session_start();
include_once 'Database.php'; // Inclure la classe de connexion à la base de données

// Fonction pour sécuriser les entrées
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);

    // Vérification des champs vides
    if (empty($email) || empty($password)) {
        header("Location: login.html?error=empty_fields");
        exit();
    }

    $db = new Database();
    $conn = $db->connect();

    // Vérification des informations de l'étudiant
    $query = "SELECT * FROM etudiants WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($etudiant && password_verify($password, $etudiant['mdp'])) {
        // Si l'étudiant existe et le mot de passe est correct
        $_SESSION['user_id'] = $etudiant['id_etudiant'];
        $_SESSION['user_role'] = 'etudiant';
        header("Location: dashboard.php");
        exit();
    }

    // Vérification des informations de l'entreprise
    $query = "SELECT * FROM entreprises WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($entreprise && password_verify($password, $entreprise['mdp'])) {
        // Si l'entreprise existe et le mot de passe est correct
        $_SESSION['user_id'] = $entreprise['id_entreprise'];
        $_SESSION['user_role'] = 'entreprise';
        header("Location: entreprise.php");
        exit();
    }

    // Si les informations sont invalides
    header("Location: login.html?error=invalid_credentials");
    exit();
}
?>
