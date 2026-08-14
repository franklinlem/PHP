<?php
require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? 'me';

if ($action === 'me') {
    if (empty($_SESSION['user_id'])) jsonResponse(['ok'=>true,'user'=>null]);
    $stmt = db()->prepare('SELECT id,name,email FROM users WHERE id=?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    jsonResponse(['ok'=>true,'user'=>$stmt->fetch(), 'csrf'=>csrfToken()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['ok'=>false,'error'=>'Método inválido'],405);
$data = inputJson();

if ($action === 'register') {
    $name = trim((string)($data['name'] ?? ''));
    $email = mb_strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');

    if (mb_strlen($name) < 2) jsonResponse(['ok'=>false,'error'=>'Informe seu nome.'],422);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['ok'=>false,'error'=>'E-mail inválido.'],422);
    if (strlen($password) < 8) jsonResponse(['ok'=>false,'error'=>'A senha deve ter pelo menos 8 caracteres.'],422);

    try {
        $stmt = db()->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)');
        $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)db()->lastInsertId();
        jsonResponse(['ok'=>true,'user'=>['id'=>$_SESSION['user_id'],'name'=>$name,'email'=>$email],'csrf'=>csrfToken()]);
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) === 1062) jsonResponse(['ok'=>false,'error'=>'Este e-mail já está cadastrado.'],409);
        jsonResponse(['ok'=>false,'error'=>'Erro ao criar conta.'],500);
    }
}

if ($action === 'login') {
    $email = mb_strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');
    $stmt = db()->prepare('SELECT id,name,email,password_hash FROM users WHERE email=?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password,$user['password_hash'])) {
        jsonResponse(['ok'=>false,'error'=>'E-mail ou senha incorretos.'],401);
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    unset($user['password_hash']);
    jsonResponse(['ok'=>true,'user'=>$user,'csrf'=>csrfToken()]);
}

if ($action === 'logout') {
    checkCsrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p=session_get_cookie_params();
        setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'] ?? '',$p['secure'],$p['httponly']);
    }
    session_destroy();
    jsonResponse(['ok'=>true]);
}

jsonResponse(['ok'=>false,'error'=>'Ação inválida'],404);
