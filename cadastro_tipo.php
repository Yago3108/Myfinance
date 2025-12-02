<?php
session_start();
require_once 'conexao.php'; 

$mensagem = "";

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION["idUsuario"])) {
    header("Location: login.php"); 
    exit();
}

$user_id = $_SESSION["idUsuario"];

$tipos_padrao_insert = [
    ['nome' => 'Salário', 'tipo' => 'G'],
    ['nome' => 'Investimentos', 'tipo' => 'G'],
    ['nome' => 'Renda Extra', 'tipo' => 'G'],
    ['nome' => 'Aluguel/Moradia', 'tipo' => 'D'],
    ['nome' => 'Alimentação', 'tipo' => 'D'],
    ['nome' => 'Transporte', 'tipo' => 'D'],
    ['nome' => 'Lazer', 'tipo' => 'D'],
    ['nome' => 'Saúde', 'tipo' => 'D'],
    ['nome' => 'Contas de Consumo', 'tipo' => 'D'], 
];

$tipos_padrao_ref = [];
foreach ($tipos_padrao_insert as $tipo) {
    $tipos_padrao_ref[$tipo['nome']] = $tipo['tipo'];
}

$sql_check = "SELECT idTipo FROM Tipos WHERE idUsuario = ? AND nome = ? AND tipo = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);

$sql_insert = "INSERT INTO Tipos (idUsuario, nome, tipo) VALUES (?, ?, ?)";
$stmt_insert = mysqli_prepare($conn, $sql_insert);

if ($stmt_check === false || $stmt_insert === false) {
    $mensagem .= "<div class='alert alert-danger'>Erro fatal ao preparar statements: " . mysqli_error($conn) . "</div>";
} else {
    $inseridos_count = 0;
    
    foreach ($tipos_padrao_insert as $tipo) {
        $nome = $tipo['nome'];
        $tipoChar = $tipo['tipo'];
        mysqli_stmt_bind_param($stmt_check, "iss", $user_id, $nome, $tipoChar);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) == 0) {
            mysqli_stmt_bind_param($stmt_insert, "iss", $user_id, $nome, $tipoChar);
            if (mysqli_stmt_execute($stmt_insert)) {
                $inseridos_count++;
            }
        }
        mysqli_stmt_free_result($stmt_check);
    }
    
    if ($inseridos_count > 0) {
        $mensagem .= "<div class='alert alert-info'>$inseridos_count categorias padrão foram adicionadas à sua conta.</div>";
    }

    mysqli_stmt_close($stmt_check);
    mysqli_stmt_close($stmt_insert);
}
if (isset($_POST['acao']) && $_POST['acao'] == 'editar_nome') {
    $tipo_id = (int)$_POST['id_tipo'];
    $novo_nome = mysqli_real_escape_string($conn, trim($_POST['novo_nome'] ?? ''));

    if (empty($novo_nome)) {
        $mensagem = "<div class='alert alert-danger'>O novo nome da categoria não pode ser vazio.</div>";
    } else {
     
        $sql_fetch_edit = "SELECT nome, tipo FROM Tipos WHERE idTipo = ? AND idUsuario = ?";
        $stmt_fetch_edit = mysqli_prepare($conn, $sql_fetch_edit);
        mysqli_stmt_bind_param($stmt_fetch_edit, "ii", $tipo_id, $user_id);
        mysqli_stmt_execute($stmt_fetch_edit);
        $resultado_edit = mysqli_stmt_get_result($stmt_fetch_edit);
        $tipo_info_edit = mysqli_fetch_assoc($resultado_edit);
        mysqli_stmt_close($stmt_fetch_edit);

        if (!$tipo_info_edit) {
            $mensagem = "<div class='alert alert-danger'>Erro: A categoria não existe ou você não tem permissão.</div>";
        } elseif (array_key_exists($tipo_info_edit['nome'], $tipos_padrao_ref) && $tipos_padrao_ref[$tipo_info_edit['nome']] === $tipo_info_edit['tipo']) {
            $mensagem = "<div class='alert alert-warning'>A categoria " . htmlspecialchars($tipo_info_edit['nome']) . " não pode ter o nome alterado, pois é um tipo padrão.</div>";
        } else {
   
            $sql_check_nome = "SELECT idTipo FROM Tipos WHERE idUsuario = ? AND nome = ? AND idTipo != ?";
            $stmt_check_nome = mysqli_prepare($conn, $sql_check_nome);
            mysqli_stmt_bind_param($stmt_check_nome, "isi", $user_id, $novo_nome, $tipo_id);
            mysqli_stmt_execute($stmt_check_nome);
            mysqli_stmt_store_result($stmt_check_nome);

            if (mysqli_stmt_num_rows($stmt_check_nome) > 0) {
                $mensagem = "<div class='alert alert-warning'>O nome " . htmlspecialchars($novo_nome) . " já está em uso por outra categoria.</div>";
            } else {

                $sql_update = "UPDATE Tipos SET nome = ? WHERE idTipo = ? AND idUsuario = ?";
                $stmt_update = mysqli_prepare($conn, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "sii", $novo_nome, $tipo_id, $user_id);

                if (mysqli_stmt_execute($stmt_update)) {
                    $mensagem = "<div class='alert alert-success'>Categoria " . htmlspecialchars($tipo_info_edit['nome']) . " renomeada para " . htmlspecialchars($novo_nome) . " com sucesso!</div>";
                } else {
                    $mensagem = "<div class='alert alert-danger'>Erro ao atualizar nome: " . mysqli_error($conn) . "</div>";
                }
                mysqli_stmt_close($stmt_update);
            }
            mysqli_stmt_close($stmt_check_nome);
        }
    }
}
if (isset($_GET['acao']) && $_GET['acao'] == 'deletar' && isset($_GET['id'])) {
    
    $tipo_id = (int)$_GET['id'];
    $sql_fetch_delete = "SELECT nome, tipo FROM Tipos WHERE idTipo = ? AND idUsuario = ?";
    $stmt_fetch_delete = mysqli_prepare($conn, $sql_fetch_delete);
    mysqli_stmt_bind_param($stmt_fetch_delete, "ii", $tipo_id, $user_id);
    mysqli_stmt_execute($stmt_fetch_delete);
    $resultado_delete = mysqli_stmt_get_result($stmt_fetch_delete);
    $tipo_info = mysqli_fetch_assoc($resultado_delete);
    mysqli_stmt_close($stmt_fetch_delete);

    if (!$tipo_info) {
        $mensagem = "<div class='alert alert-danger'>Erro: O tipo de transação não existe ou você não tem permissão para excluí-lo.</div>";
    } else {

        if (array_key_exists($tipo_info['nome'], $tipos_padrao_ref) && $tipos_padrao_ref[$tipo_info['nome']] === $tipo_info['tipo']) {
             $mensagem = "<div class='alert alert-warning'>Categoria " . htmlspecialchars($tipo_info['nome']) . " não pode ser excluída, pois é um tipo padrão do sistema.</div>";
        } else {

            $sql_delete = "DELETE FROM Tipos WHERE idTipo = ? AND idUsuario = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete);
            mysqli_stmt_bind_param($stmt_delete, "ii", $tipo_id, $user_id);
            
            if (mysqli_stmt_execute($stmt_delete)) {
                if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                    $mensagem = "<div class='alert alert-success'>Categoria " . htmlspecialchars($tipo_info['nome']) . " excluída com sucesso!</div>";
                } else {
                    $mensagem = "<div class='alert alert-danger'>Erro ao excluir: Nenhuma linha afetada.</div>";
                }
            } else {
    
                $erro_bd = mysqli_error($conn);
                if (strpos($erro_bd, 'foreign key constraint fails') !== false) {
                     $mensagem = "<div class='alert alert-danger'>Erro de Integridade: Não foi possível excluir a categoria " . htmlspecialchars($tipo_info['nome']) . ". Existem transações vinculadas a ela. Exclua as transações primeiro.</div>";
                } else {
                    $mensagem = "<div class='alert alert-danger'>Erro ao excluir tipo: " . $erro_bd . "</div>";
                }
            }
            mysqli_stmt_close($stmt_delete);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['acao']) || $_POST['acao'] != 'editar_nome')) {
    
    $nome_tipo = mysqli_real_escape_string($conn, trim($_POST['nome_tipo'] ?? ''));
    $tipo = mysqli_real_escape_string($conn, $_POST['tipo'] ?? '');

    if (empty($nome_tipo) || empty($tipo)) {
        $mensagem = "<div class='alert alert-danger'>Por favor, preencha o nome do tipo e selecione Gasto ou Ganho.</div>";
    } else {
        $sql_check_custom = "SELECT idTipo FROM Tipos WHERE idUsuario = ? AND nome = ?";
        $stmt_check_custom = mysqli_prepare($conn, $sql_check_custom);
        mysqli_stmt_bind_param($stmt_check_custom, "is", $user_id, $nome_tipo);
        mysqli_stmt_execute($stmt_check_custom);
        mysqli_stmt_store_result($stmt_check_custom);

        if (mysqli_stmt_num_rows($stmt_check_custom) > 0) {
             $mensagem = "<div class='alert alert-warning'>A categoria " . htmlspecialchars($nome_tipo) . " já existe.</div>";
        } else {

            $sql_insert_custom = "INSERT INTO Tipos (idUsuario, nome, tipo) VALUES (?, ?, ?)";
            $stmt_insert_custom = mysqli_prepare($conn, $sql_insert_custom);
            
            mysqli_stmt_bind_param($stmt_insert_custom, "iss", $user_id, $nome_tipo, $tipo);
            
            if (mysqli_stmt_execute($stmt_insert_custom)) {
                $mensagem = "<div class='alert alert-success'>Categoria " . htmlspecialchars($nome_tipo) . " cadastrada com sucesso!</div>";
            } else {
                $mensagem = "<div class='alert alert-danger'>Erro ao cadastrar tipo: " . mysqli_error($conn) . "</div>";
            }
            mysqli_stmt_close($stmt_insert_custom);
        }
        mysqli_stmt_close($stmt_check_custom);
    }
}

$tipos_usuario_db = [];
$sql_fetch = "SELECT idTipo AS id, nome, tipo FROM Tipos WHERE idUsuario = ? ORDER BY tipo DESC, nome ASC";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);
mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
mysqli_stmt_execute($stmt_fetch);
$resultado = mysqli_stmt_get_result($stmt_fetch);

while ($row = mysqli_fetch_assoc($resultado)) {
    $row['custom'] = true;

    if (array_key_exists($row['nome'], $tipos_padrao_ref) && $tipos_padrao_ref[$row['nome']] === $row['tipo']) {
        unset($row['custom']);
    }
    $tipos_usuario_db[] = $row;
}

mysqli_stmt_close($stmt_fetch);
$ganhos_list = array_filter($tipos_usuario_db, fn($t) => $t['tipo'] === 'G');
$gastos_list = array_filter($tipos_usuario_db, fn($t) => $t['tipo'] === 'D');

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - MyFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="cadastro_tipo.css">
</head>
<body>

<div class="container">
    <div class="card card-cadastro p-4 mx-auto">
        <div class="card-body">
            <h2 class="text-center mb-4" style="color: var(--dark-blue-primary); font-weight: 700;">
                Gerenciar Categorias
            </h2>
            <?php echo $mensagem;?>
            <div class="row">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="p-3 border rounded shadow-sm bg-white">
                        <h4 class="mb-3 text-center" style="color: var(--dark-blue-light);">Adicionar Nova Categoria</h4>
                        <form method="POST" action="cadastro_tipo.php">
                            
                            <div class="mb-3">
                                <label for="nome_tipo" class="form-label">Nome da Categoria</label>
                                <input type="text" class="form-control" id="nome_tipo" name="nome_tipo" placeholder="Ex: Renda Passiva" required>
                            </div>

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
                <div class="col-lg-7">
                    <div class="p-3 border rounded shadow-sm bg-white">
                        <h4 class="mb-3 text-center" style="color: var(--dark-blue-light);">Minhas Categorias</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-center" style="color: var(--color-ganho);"><i class="fas fa-money-check-alt"></i> Ganhos</h5>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($ganhos_list as $tipo): ?>
                                        <li class="list-group-item list-group-item-ganho d-flex justify-content-between align-items-center">
                                            <?php echo htmlspecialchars($tipo['nome']); ?>
                                            <span>
                                                <?php if (isset($tipo['custom'])): ?>
                                                    <button class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditar" 
                                                            data-id="<?php echo $tipo['id']; ?>" data-nome="<?php echo htmlspecialchars($tipo['nome']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="cadastro_tipo.php?acao=deletar&id=<?php echo $tipo['id']; ?>" class="btn btn-delete btn-sm" 
                                                       onclick="return confirm('Tem certeza que deseja excluir a categoria <?php echo htmlspecialchars($tipo['nome']); ?>?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Padrão</span>
                                                <?php endif; ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <h5 class="text-center" style="color: var(--color-gasto);"><i class="fas fa-shopping-cart"></i> Gastos</h5>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($gastos_list as $tipo): ?>
                                        <li class="list-group-item list-group-item-gasto d-flex justify-content-between align-items-center">
                                            <?php echo htmlspecialchars($tipo['nome']); ?>
                                            <span>
                                                <?php if (isset($tipo['custom'])): ?>
                                                    <button class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditar" 
                                                            data-id="<?php echo $tipo['id']; ?>" data-nome="<?php echo htmlspecialchars($tipo['nome']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="cadastro_tipo.php?acao=deletar&id=<?php echo $tipo['id']; ?>" class="btn btn-delete btn-sm"
                                                       onclick="return confirm('Tem certeza que deseja excluir a categoria <?php echo htmlspecialchars($tipo['nome']); ?>?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Padrão</span>
                                                <?php endif; ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="pagina_inicial.php" class="text-decoration-none" >
                    <i class="fas fa-home"></i> Voltar para a Página Inicial
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarLabel">Editar Nome da Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="cadastro_tipo.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar_nome">
                    <input type="hidden" name="id_tipo" id="modal-id-tipo">
                    
                    <div class="mb-3">
                        <label for="modal-novo-nome" class="form-label">Novo Nome:</label>
                        <input type="text" class="form-control" id="modal-novo-nome" name="novo_nome" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

    document.addEventListener('DOMContentLoaded', function () {
        const modalEditar = document.getElementById('modalEditar');
        if (modalEditar) {
            modalEditar.addEventListener('show.bs.modal', function (event) {

                const button = event.relatedTarget;
               
                const idTipo = button.getAttribute('data-id');
                const nomeAtual = button.getAttribute('data-nome');

      
                const modalIdTipo = modalEditar.querySelector('#modal-id-tipo');
                const modalNovoNome = modalEditar.querySelector('#modal-novo-nome');

                modalIdTipo.value = idTipo;
                modalNovoNome.value = nomeAtual;
                modalNovoNome.focus(); 
            });
        }
    });
</script>
</body>
</html>