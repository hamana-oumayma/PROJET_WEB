<?php
session_start();
require_once __DIR__ . '/../database.php';




$id_candidature = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statut = filter_input(INPUT_GET, 'statut', FILTER_SANITIZE_STRING);

if (!$id_candidature || !in_array($statut, ['accepte', 'refuse'])) {
    header("Location: ../entreprise.php");
    exit();
}

$db = (new Database())->connect();
$stmt = $db->prepare("UPDATE candidatures SET statut = ? WHERE id_candidature = ?");
$stmt->execute([$statut, $id_candidature]);

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../entreprise.php'));
exit();
?>
