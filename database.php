<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'gestion_stages';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        $this->conn = null;
    
        try {
            // Connexion sans sélection de base
            $this->conn = new PDO(
                'mysql:host=' . $this->host,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Création de la base si elle n'existe pas
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS `$this->db_name`");
            $this->conn->exec("USE `$this->db_name`");
            
        } catch(PDOException $e) {
            echo 'Erreur de connexion : ' . $e->getMessage();
        }
    
        return $this->conn;
    }

    public function createTables() {
        try {
            $conn = $this->connect();
            
            // Script SQL pour créer les tables
            $sql = "
            CREATE TABLE IF NOT EXISTS etudiants (
                id_etudiant INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(50) NOT NULL,
                prenom VARCHAR(50) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                mdp VARCHAR(255) NOT NULL,
                date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
                est_valide BOOLEAN 
            );

            CREATE TABLE IF NOT EXISTS entreprises (
                id_entreprise INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                adresse TEXT,
                email VARCHAR(100) UNIQUE NOT NULL,
                telephone VARCHAR(20),
                mdp VARCHAR(255) NOT NULL,
                date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
                est_valide BOOLEAN 
            );
    CREATE TABLE IF NOT EXISTS admin (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mdp VARCHAR(255) NOT NULL
);


            CREATE TABLE IF NOT EXISTS offres_stage (
                id_offre INT AUTO_INCREMENT PRIMARY KEY,
                id_entreprise INT NOT NULL,
                titre VARCHAR(100) NOT NULL,
                description TEXT,
                date_debut DATE NOT NULL,
                date_fin DATE NOT NULL,
                date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
                lettre_motivation_requise BOOLEAN DEFAULT FALSE,
                
                FOREIGN KEY (id_entreprise) REFERENCES entreprises(id_entreprise),
                est_valide BOOLEAN 
            );

            CREATE TABLE IF NOT EXISTS candidatures (
                id_candidature INT AUTO_INCREMENT PRIMARY KEY,
                id_etudiant INT NOT NULL,
                id_offre INT NOT NULL,
                date_candidature DATETIME DEFAULT CURRENT_TIMESTAMP,
                statut ENUM('en_attente', 'accepte', 'refuse') DEFAULT 'en_attente',
                notes TEXT,
                FOREIGN KEY (id_etudiant) REFERENCES etudiants(id_etudiant),
                FOREIGN KEY (id_offre) REFERENCES offres_stage(id_offre)
            );

            CREATE TABLE IF NOT EXISTS documents (
                id_document INT AUTO_INCREMENT PRIMARY KEY,
                id_candidature INT NOT NULL,
                type_document ENUM('cv', 'lettre_motivation', 'convention_stage', 'autre'),
                chemin_fichier VARCHAR(255) NOT NULL,
                date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_candidature) REFERENCES candidatures(id_candidature)
            );
          CREATE TABLE IF NOT EXISTS avis_entreprises (
    id_avis INT AUTO_INCREMENT PRIMARY KEY,
    id_etudiant INT NOT NULL,
    id_entreprise INT NOT NULL,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    date_avis DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_etudiant) REFERENCES etudiants(id_etudiant),
    FOREIGN KEY (id_entreprise) REFERENCES entreprises(id_entreprise)
);
            ";

            $conn->exec($sql);
            echo "Tables créées avec succès!";
        } catch(PDOException $e) {
            echo "Erreur lors de la création des tables: " . $e->getMessage();
        }
    }
}
?>