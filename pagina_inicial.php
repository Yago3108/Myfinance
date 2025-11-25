<?php
session_start();
require_once 'conexao.php'; 

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION["idUsuario"])) {
    header("Location: login.php"); 
    exit();
}

$user_id = $_SESSION["idUsuario"];
$mensagem = "";

$categorias = [
    'G' => [], 
    'D' => []  
];
      
$sql_fetch = "SELECT idTipo, nome, tipo FROM Tipos WHERE idUsuario = ? ORDER BY nome ASC";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);

if ($stmt_fetch) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
    mysqli_stmt_execute($stmt_fetch);
    $resultado = mysqli_stmt_get_result($stmt_fetch);

    while ($row = mysqli_fetch_assoc($resultado)) {
        $categorias[$row['tipo']][] = $row;
    }
    mysqli_stmt_close($stmt_fetch);
} else {
    $mensagem .= "<div class='alert alert-danger'>Erro ao buscar categorias: " . mysqli_error($conn) . "</div>";
}
$categorias_json = json_encode($categorias);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="style.css"> 
    <link rel="shortcut icon" href="myfinancefavicon.png" type="image/x-icon">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="">MyFinance</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                      <button type="button" class="btn btn-lg btn-cadastro text-white shadow" data-bs-toggle="modal" data-bs-target="#transacaoModal">
                    <i class="fas fa-plus-circle"></i> Cadastrar Nova Transação
                       </button>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="cadastro_tipo">Cadastrar tipos</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<div class="modal fade" id="transacaoModal" tabindex="-1" aria-labelledby="transacaoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transacaoModalLabel"><i class="fa-solid fa-coins"></i> Registrar Transação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processa_transacao.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label d-block fw-bold">Selecione o Tipo:</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo" id="tipoGanho" value="G" required checked>
                            <label class="form-check-label" for="tipoGanho" style="color: var(--color-ganho);"><i class="fas fa-arrow-up"></i> Ganho</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo" id="tipoGasto" value="D">
                            <label class="form-check-label" for="tipoGasto" style="color: var(--color-gasto);"><i class="fas fa-arrow-down"></i> Gasto</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome/Título da Transação</label>
                        <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Salário Mensal" required>
                    </div>
                    <div class="mb-3">
                        <label for="valor" class="form-label">Valor (R$)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="valor" name="valor" required>
                    </div>
                    <div class="mb-3">
                        <label for="dataTransacao" class="form-label">Data</label>
                        <input type="date" class="form-control" id="dataTransacao" name="dataTransacao" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="idTipo" class="form-label fw-bold">Categoria</label>
                        <select class="form-select" id="idTipo" name="idTipo" required>
                            <option value="" disabled selected>Selecione um tipo (Ganho ou Gasto)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="2" placeholder="Detalhes sobre a transação..."></textarea>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-cadastro text-white">Salvar Transação</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    const categorias = <?php echo $categorias_json; ?>;
    const selectTipo = document.getElementById('idTipo');
    const radioGanhos = document.getElementById('tipoGanho');
    const radioGastos = document.getElementById('tipoGasto');
    function atualizarCategorias(tipo) {
        selectTipo.innerHTML = '<option value="" disabled selected>Selecione uma categoria...</option>';
        
        const listaCategorias = categorias[tipo] || [];
        
        if (listaCategorias.length === 0) {
            const defaultOption = document.createElement('option');
            defaultOption.value = "";
            defaultOption.textContent = (tipo === 'G') 
                ? "Nenhuma categoria de Ganho cadastrada." 
                : "Nenhuma categoria de Gasto cadastrada.";
            selectTipo.appendChild(defaultOption);
            selectTipo.setAttribute('disabled', true);
        } else {
            listaCategorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.idTipo;
                option.textContent = cat.nome;
                selectTipo.appendChild(option);
            });
            selectTipo.removeAttribute('disabled');
        }
    }
    document.querySelectorAll('input[name="tipo"]').forEach(radio => {
        radio.addEventListener('change', (event) => {
            atualizarCategorias(event.target.value);
        });
    });
    document.addEventListener('DOMContentLoaded', () => {
        if (radioGanhos.checked) {
            atualizarCategorias('G');
        } else if (radioGastos.checked.checked) {
            atualizarCategorias('D');
        }
    });

    const transacaoModal = document.getElementById('transacaoModal')
    transacaoModal.addEventListener('show.bs.modal', event => {
        radioGanhos.checked = true;
        atualizarCategorias('G');
    });

</script>
    <section id="hero">
        <div class="container">
        
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>