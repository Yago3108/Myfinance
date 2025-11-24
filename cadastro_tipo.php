<?php
session_start();

// Inclui o arquivo de conexão. Certifique-se de que 'conexao.php' esteja no mesmo diretório.
require_once 'conexao.php'; 

// --- DEFINIÇÃO DOS TIPOS PADRÃO ---
// Estes tipos não serão armazenados no DB, mas serão exibidos na lista e não poderão ser deletados.
$tipos_padrao = [
    // Ganhos (Receitas)
    ['nome' => 'Salário', 'tipo' => 'G', 'id' => 'default_g1'],
    ['nome' => 'Investimentos', 'tipo' => 'G', 'id' => 'default_g2'],
    ['nome' => 'Renda Extra', 'tipo' => 'G', 'id' => 'default_g3'],
    // Despesas (Gastos)
    ['nome' => 'Aluguel/Moradia', 'tipo' => 'D', 'id' => 'default_d1'],
    ['nome' => 'Alimentação', 'tipo' => 'D', 'id' => 'default_d2'],
    ['nome' => 'Transporte', 'tipo' => 'D', 'id' => 'default_d3'],
    ['nome' => 'Lazer', 'tipo' => 'D', 'id' => 'default_d4'],
    ['nome' => 'Saúde', 'tipo' => 'D', 'id' => 'default_d5'],
];


// 1. VERIFICAÇÃO DE LOGIN
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    // Redireciona para a página de login se o usuário não estiver logado
    header("Location: login.php"); // Corrigido para login.php
    exit();
}

$mensagem = ""; // Variável para armazenar mensagens de sucesso ou erro
$user_id = $_SESSION['user_id']; 


// 2. PROCESSAMENTO DE EXCLUSÃO (VIA GET)
if (isset($_GET['acao']) && $_GET['acao'] == 'deletar' && isset($_GET['id'])) {
    
    $tipo_id = (int)$_GET['id'];
    
    // ATUALIZADO: Usando a tabela 'Tipos', campos 'idTipo' e 'idUsuario'
    $sql_delete = "DELETE FROM Tipos WHERE idTipo = ? AND idUsuario = ?";
    $stmt_delete = mysqli_prepare($conn, $sql_delete);
    
    // "ii" -> i (integer para idTipo), i (integer para idUsuario)
    mysqli_stmt_bind_param($stmt_delete, "ii", $tipo_id, $user_id);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
            $mensagem = "<div class='alert alert-warning'>Tipo de transação excluído com sucesso!</div>";
        } else {
            $mensagem = "<div class='alert alert-danger'>Erro: O tipo de transação não existe ou você não tem permissão para excluí-lo.</div>";
        }
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao excluir tipo: " . mysqli_error($conn) . "</div>";
    }
    
    mysqli_stmt_close($stmt_delete);
}


// 3. PROCESSAMENTO DE INSERÇÃO (VIA POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Coleta e limpa os dados do formulário
    $nome_tipo = mysqli_real_escape_string($conn, trim($_POST['nome_tipo']));
    $tipo = mysqli_real_escape_string($conn, $_POST['tipo']); // 'G' para Ganho, 'D' para Despesa

    // Validação básica
    if (empty($nome_tipo) || empty($tipo)) {
        $mensagem = "<div class='alert alert-danger'>Por favor, preencha o nome do tipo e selecione Gasto ou Ganho.</div>";
    } else {
        
        // ATUALIZADO: Usando a tabela 'Tipos' e o campo 'idUsuario'
        $sql_insert = "INSERT INTO Tipos (idUsuario, nome, tipo) VALUES (?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $sql_insert);
        
        // "iss" -> i (integer para idUsuario), s (string para nome), s (string para tipo)
        mysqli_stmt_bind_param($stmt_insert, "iss", $user_id, $nome_tipo, $tipo);
        
        if (mysqli_stmt_execute($stmt_insert)) {
            $mensagem = "<div class='alert alert-success'>Tipo de transação **$nome_tipo** ($tipo) cadastrado com sucesso!</div>";
        } else {
            // Verifica se o erro é de duplicidade, se o seu DB suportar índices únicos no nome
            $mensagem = "<div class='alert alert-danger'>Erro ao cadastrar tipo: " . mysqli_error($conn) . "</div>";
        }
        
        mysqli_stmt_close($stmt_insert);
    }
}


// 4. RECUPERAÇÃO DE TODOS OS TIPOS DO USUÁRIO
$tipos_usuario = [];
// ATUALIZADO: Selecionando de 'Tipos', 'idTipo' com alias 'id' para manter a compatibilidade do front-end e 'idUsuario'
$sql_fetch = "SELECT idTipo AS id, nome, tipo FROM Tipos WHERE idUsuario = ?";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);
mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
mysqli_stmt_execute($stmt_fetch);
$resultado = mysqli_stmt_get_result($stmt_fetch);

while ($row = mysqli_fetch_assoc($resultado)) {
    // Adiciona uma flag 'custom' para identificar que pode ser deletado
    $row['custom'] = true; 
    $tipos_usuario[] = $row;
}

mysqli_stmt_close($stmt_fetch);


// 5. COMBINAÇÃO DOS TIPOS PADRÃO COM OS TIPOS DO USUÁRIO
// Filtra e organiza os tipos
$todos_tipos = array_merge($tipos_padrao, $tipos_usuario);

$ganhos_list = array_filter($todos_tipos, fn($t) => $t['tipo'] === 'G');
$gastos_list = array_filter($todos_tipos, fn($t) => $t['tipo'] === 'D');


// 6. FECHA A CONEXÃO
mysqli_close($conn); 

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Tipos de Transação - MyFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Cores Personalizadas para MyFinance -->
    <style>
        :root {
            --dark-blue-primary: #0D47A1;
            --dark-blue-light: #1565C0;
            --background-gradient: linear-gradient(135deg, #0D47A1 0%, #001f50 100%);
            --color-ganho: #28a745;
            --color-gasto: #dc3545;
        }
        body {
            background: var(--background-gradient);
            min-height: 100vh;
            padding-top: 50px;
            padding-bottom: 50px;
        }
        .card-cadastro {
            max-width: 900px;
            width: 95%;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        .btn-submit {
            background-color: var(--dark-blue-light);
            border: none;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: var(--dark-blue-primary);
        }
        .list-group-item-ganho { border-left: 4px solid var(--color-ganho); }
        .list-group-item-gasto { border-left: 4px solid var(--color-gasto); }
        .btn-delete { color: #fff; background-color: var(--color-gasto); border: none; padding: 5px 10px; border-radius: 5px; }
        .btn-delete:hover { background-color: #c82333; }
    </style>
</head>
<body>

<div class="card card-cadastro p-4 mx-auto">
    <div class="card-body">
        <h2 class="text-center mb-4" style="color: var(--dark-blue-primary); font-weight: 700;">
            Gerenciar Categorias
        </h2>

        <?php echo $mensagem; // Exibe mensagem de sucesso ou erro ?>

        <div class="row">
            
            <!-- COLUNA ESQUERDA: CADASTRO DE NOVO TIPO -->
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="p-3 border rounded shadow-sm">
                    <h4 class="mb-3 text-center" style="color: var(--dark-blue-light);">Adicionar Nova Categoria</h4>
                    <form method="POST" action="cadastro_tipo_transacao.php">
                        
                        <!-- Campo Nome do Tipo -->
                        <div class="mb-3">
                            <label for="nome_tipo" class="form-label">Nome da Categoria</label>
                            <input type="text" class="form-control" id="nome_tipo" name="nome_tipo" placeholder="Ex: Renda Passiva" required>
                        </div>

                        <!-- Campo Tipo (Ganho ou Despesa) -->
                        <div class="mb-4">
                            <label class="form-label d-block">Tipo:</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoGanho" value="G" required>
                                <label class="form-check-label" for="tipoGanho"><span style="color: var(--color-ganho);"><i class="fas fa-arrow-up"></i> Ganho</span></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoDespesa" value="D">
                                <label class="form-check-label" for="tipoDespesa"><span style="color: var(--color-gasto);"><i class="fas fa-arrow-down"></i> Gasto</span></label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-submit text-white w-100">
                            <i class="fas fa-plus-circle"></i> Cadastrar
                        </button>
                    </form>
                </div>
            </div>

            <!-- COLUNA DIREITA: LISTA DE TIPOS CADASTRADOS -->
            <div class="col-lg-7">
                <div class="p-3 border rounded shadow-sm">
                    <h4 class="mb-3 text-center" style="color: var(--dark-blue-light);">Minhas Categorias</h4>
                    
                    <div class="row">
                        <!-- Lista de Ganhos -->
                        <div class="col-md-6">
                            <h5 class="text-center" style="color: var(--color-ganho);"><i class="fas fa-money-check-alt"></i> Ganhos</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($ganhos_list as $tipo): ?>
                                    <li class="list-group-item list-group-item-ganho d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($tipo['nome']); ?>
                                        <?php if (isset($tipo['custom'])): // Se for um item customizado do usuário, mostra o botão de deletar ?>
                                            <a href="cadastro_tipo_transacao.php?acao=deletar&id=<?php echo $tipo['id']; ?>" class="btn btn-delete btn-sm" 
                                               onclick="return confirm('Tem certeza que deseja excluir a categoria <?php echo htmlspecialchars($tipo['nome']); ?>? Esta ação não pode ser desfeita e pode afetar transações existentes.');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php else: // Tipo padrão ?>
                                            <span class="badge bg-secondary">Padrão</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Lista de Gastos -->
                        <div class="col-md-6 mt-3 mt-md-0">
                            <h5 class="text-center" style="color: var(--color-gasto);"><i class="fas fa-shopping-cart"></i> Gastos</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($gastos_list as $tipo): ?>
                                    <li class="list-group-item list-group-item-gasto d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($tipo['nome']); ?>
                                        <?php if (isset($tipo['custom'])): // Se for um item customizado do usuário, mostra o botão de deletar ?>
                                            <a href="cadastro_tipo_transacao.php?acao=deletar&id=<?php echo $tipo['id']; ?>" class="btn btn-delete btn-sm"
                                                onclick="return confirm('Tem certeza que deseja excluir a categoria <?php echo htmlspecialchars($tipo['nome']); ?>? Esta ação não pode ser desfeita e pode afetar transações existentes.');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php else: // Tipo padrão ?>
                                            <span class="badge bg-secondary">Padrão</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>

        <div class="mt-4 text-center">
            <a href="dashboard.php" class="text-decoration-none" style="color: var(--dark-blue-primary); font-weight: 600;">
                <i class="fas fa-home"></i> Voltar para o Dashboard
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>