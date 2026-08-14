<?php
declare(strict_types=1);
const DB_HOST='localhost';
const DB_NAME='u568310789_tarefas';
const DB_USER='u568310789_frank_tarefas';
const DB_PASS='COLOQUE_AQUI_A_SENHA_DO_MYSQL';
session_name('franklem_tasks'); session_set_cookie_params(['lifetime'=>2592000,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); session_start();
function out($d,$s=200){http_response_code($s);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($d,JSON_UNESCAPED_UNICODE);exit;}
function db(){static $p; if($p)return $p; try{$p=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);return $p;}catch(Throwable $e){error_log($e->getMessage());out(['ok'=>false,'error'=>'Falha no MySQL. Confira a senha em config.php.'],500);}}
function body(){return json_decode(file_get_contents('php://input')?:'{}',true)?:[];}
function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24));return $_SESSION['csrf'];}
function guard(){if(empty($_SESSION['user']))out(['ok'=>false,'error'=>'Não autenticado.'],401);return (int)$_SESSION['user'];}
function check(){if(!hash_equals($_SESSION['csrf']??'',$_SERVER['HTTP_X_CSRF_TOKEN']??''))out(['ok'=>false,'error'=>'Sessão expirada. Recarregue a página.'],403);}
?>