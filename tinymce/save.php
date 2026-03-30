<?php
// Configuration CORS pour les requêtes depuis le navigateur
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Gestion des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Connexion à la base de données
$servername = "db";  // Nom du service Docker MySQL
$username = "user";
$password = "password";
$dbname = "mini_projet";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Vérifier la connexion
    if ($conn->connect_error) {
        throw new Exception("Connexion échouée: " . $conn->connect_error);
    }
    
    // Traiter la requête POST (Sauvegarde)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['content']) || !isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes (content et id requis)']);
            exit();
        }
        
        $id = intval($data['id']);
        $content = $data['content'];
        $title = isset($data['title']) ? $data['title'] : 'Sans titre';
        
        // Vérifier si le document existe déjà
        $check_sql = "SELECT id FROM documents WHERE id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Mise à jour
            $update_sql = "UPDATE documents SET title = ?, content = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $title, $content, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Document mis à jour', 'id' => $id]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la mise à jour: ' . $stmt->error]);
            }
        } else {
            // Insertion
            $insert_sql = "INSERT INTO documents (id, title, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iss", $id, $title, $content);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Document créé', 'id' => $id]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de l\'insertion: ' . $stmt->error]);
            }
        }
        $stmt->close();
    }
    // Traiter la requête GET (Récupération)
    else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 1;
        
        $select_sql = "SELECT id, title, content, created_at, updated_at FROM documents WHERE id = ?";
        $stmt = $conn->prepare($select_sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            // Retourner un document vide s'il n'existe pas
            echo json_encode(['success' => true, 'data' => [
                'id' => $id,
                'title' => 'Nouveau document',
                'content' => '<p>Écris ici ton texte...</p>',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]]);
        }
        $stmt->close();
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
    }
    
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>
