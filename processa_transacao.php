<?php
session_start();

require_once 'conexao.php'; 
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION["idUsuario"])) {
    header("Location: login.php"); 
    exit();
}

$user_id = $_SESSION["idUsuario"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: pagina_inicial.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$dataTransacao = trim($_POST['dataTransacao'] ?? '');
$tipo = trim($_POST['tipo'] ?? '');

$valor = floatval(str_replace(',', '.', trim($_POST['valor'] ?? '0'))); 
$idTipo = (int)($_POST['idTipo'] ?? 0);

if (empty($nome) || $valor <= 0 || empty($dataTransacao) || $idTipo <= 0 || !in_array($tipo, ['G', 'D'])) {
     header("Location: pagina_inicial.php?");
     exit();
}

$tabela = '';
$msg_sucesso_tipo = '';

if ($tipo === 'G') {
    $tabela = 'ganhos';
    $msg_sucesso_tipo = 'Ganho';
} elseif ($tipo === 'D') {
    $tabela = 'gastos';
    $msg_sucesso_tipo = 'Gasto';
}

if (empty($tabela)) {
    header("Location: pagina_inicial.php?status=erro&msg=" . urlencode("Erro: Tipo de transação (G/D) inválido."));
    exit();
}
$sql = "INSERT INTO $tabela (idTipo, idUsuario, nome, descricao, valor, dataTransacao) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt === false) {
    $erro = mysqli_error($conn);
    error_log("Erro na preparação do SQL ($tabela): $erro");
    header("Location: pagina_inicial.php");
    exit();
}
mysqli_stmt_bind_param($stmt, "iissds", $idTipo, $user_id, $nome, $descricao, $valor, $dataTransacao);

if (mysqli_stmt_execute($stmt)) {
    header("Location: pagina_inicial.php");
} else {
    $erro = mysqli_error($conn);
    error_log("Erro na execução do SQL ($tabela): $erro");
    header("Location: pagina_inicial.php");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit();
?>