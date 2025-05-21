<?php
session_start();
include_once 'Database.php';

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);

    if (empty($email) || empty($password)) {
        header("Location: login.html?error=empty_fields");
        exit();
    }

    $db = new Database();
    $conn = $db->connect();

    // 1. Vérification étudiant
    $query = "SELECT * FROM etudiants WHERE email = :email  AND est_valide = 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($etudiant && password_verify($password, $etudiant['mdp'])) {
        $_SESSION['user_id'] = $etudiant['id_etudiant'];
        $_SESSION['user_role'] = 'etudiant';
        header("Location: dashboard.php");
        exit();
    }

    // 2. Vérification entreprise
    $query = "SELECT * FROM entreprises WHERE email = :email  AND est_valide = 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($entreprise && password_verify($password, $entreprise['mdp'])) {
        $_SESSION['user_id'] = $entreprise['id_entreprise'];
        $_SESSION['user_role'] = 'entreprise';
        header("Location: entreprise.php");
        exit();
    }

    // 3. Vérification admin 
    $query = "SELECT * FROM admin WHERE email = :email AND mdp = :password";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $_SESSION['user_id'] = $admin['id_admin'];
        $_SESSION['user_role'] = 'admin';
        header("Location: admin.php");
        exit();
    }

    // Si aucune correspondance
    header("Location: login.html?error=invalid_credentials");
    exit();
}
?>