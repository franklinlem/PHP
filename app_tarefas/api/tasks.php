<?php
require_once __DIR__ . '/../config.php';
$userId = requireUser();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = db()->prepare('SELECT id,title,description,due_date AS dueDate,priority,category,completed AS done,created_at AS createdAt,updated_at AS updatedAt FROM tasks WHERE user_id=? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    $tasks = $stmt->fetchAll();
    foreach ($tasks as &$t) $t['done'] = (bool)$t['done'];
    jsonResponse(['ok'=>true,'tasks'=>$tasks,'csrf'=>csrfToken()]);
}

checkCsrf();
$data = inputJson();

if ($method === 'POST') {
    $title=trim((string)($data['title']??''));
    if (!$title) jsonResponse(['ok'=>false,'error'=>'Informe a tarefa.'],422);
    $stmt=db()->prepare('INSERT INTO tasks(user_id,title,description,due_date,priority,category,completed) VALUES(?,?,?,?,?,?,?)');
    $stmt->execute([$userId,$title,trim((string)($data['description']??'')) ?: null,$data['dueDate'] ?: null,in_array($data['priority']??'', ['baixa','media','alta'],true)?$data['priority']:'media',trim((string)($data['category']??'')) ?: null,!empty($data['done'])?1:0]);
    jsonResponse(['ok'=>true,'id'=>(int)db()->lastInsertId()],201);
}

$id=(int)($data['id']??0);
if (!$id) jsonResponse(['ok'=>false,'error'=>'ID inválido'],422);

if ($method === 'PUT') {
    $title=trim((string)($data['title']??''));
    if (!$title) jsonResponse(['ok'=>false,'error'=>'Informe a tarefa.'],422);
    $stmt=db()->prepare('UPDATE tasks SET title=?,description=?,due_date=?,priority=?,category=?,completed=? WHERE id=? AND user_id=?');
    $stmt->execute([$title,trim((string)($data['description']??'')) ?: null,$data['dueDate'] ?: null,in_array($data['priority']??'', ['baixa','media','alta'],true)?$data['priority']:'media',trim((string)($data['category']??'')) ?: null,!empty($data['done'])?1:0,$id,$userId]);
    jsonResponse(['ok'=>true]);
}

if ($method === 'DELETE') {
    $stmt=db()->prepare('DELETE FROM tasks WHERE id=? AND user_id=?');
    $stmt->execute([$id,$userId]);
    jsonResponse(['ok'=>true]);
}
jsonResponse(['ok'=>false,'error'=>'Método inválido'],405);
