<?php 
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $stmt = $pdo->prepare("
        SELECT 
          u.id_user,
          u.username,
          u.nome_fantasquadra,
          u.flag_admin,
          u.theme_preference,
          f.immagine_fantallenatore AS avatar_url
        FROM users u
        LEFT JOIN fantasquadra f
          ON f.nome_fantasquadra = u.nome_fantasquadra
        WHERE u.id_user = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch() ?: [
        'id_user' => (int)$_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'Utente',
        'nome_fantasquadra' => $_SESSION['nome_fantasquadra'] ?? '',
        'flag_admin' => 0,
        'theme_preference' => 'auto',
        'avatar_url' => ''
    ];
} catch (PDOException $e) {
    $u = [
        'id_user' => (int)($_SESSION['user_id'] ?? 0),
        'username' => $_SESSION['username'] ?? 'Utente',
        'nome_fantasquadra' => $_SESSION['nome_fantasquadra'] ?? '',
        'flag_admin' => 0,
        'theme_preference' => 'auto',
        'avatar_url' => ''
    ];
}

?>