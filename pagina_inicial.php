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
$total_ganhos = 0.00;
$total_gastos = 0.00;
$top_ganhos = [];
$top_gastos = [];

$sql_totais = "
    SELECT 
        (SELECT SUM(valor) FROM ganhos WHERE idUsuario = ?) AS total_ganhos,
        (SELECT SUM(valor) FROM gastos WHERE idUsuario = ?) AS total_gastos
";
$stmt_totais = mysqli_prepare($conn, $sql_totais);

if ($stmt_totais) {
    mysqli_stmt_bind_param($stmt_totais, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt_totais);
    $resultado_totais = mysqli_stmt_get_result($stmt_totais);
    $totais = mysqli_fetch_assoc($resultado_totais);
    
    $total_ganhos = $totais['total_ganhos'] ?? 0.00;
    $total_gastos = $totais['total_gastos'] ?? 0.00;
    
    $saldo_total = $total_ganhos - $total_gastos;
    mysqli_stmt_close($stmt_totais);
}

$sql_top_gastos = "
    SELECT 
        g.nome, g.valor, t.nome AS tipo_nome 
    FROM 
        gastos g 
    JOIN 
        Tipos t ON g.idTipo = t.idTipo 
    WHERE 
        g.idUsuario = ? 
    ORDER BY 
        g.valor DESC 
    LIMIT 10
";
$stmt_top_gastos = mysqli_prepare($conn, $sql_top_gastos);
if ($stmt_top_gastos) {
    mysqli_stmt_bind_param($stmt_top_gastos, "i", $user_id);
    mysqli_stmt_execute($stmt_top_gastos);
    $top_gastos = mysqli_fetch_all(mysqli_stmt_get_result($stmt_top_gastos), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_top_gastos);
}

$sql_top_ganhos = "
    SELECT 
        g.nome, g.valor, t.nome AS tipo_nome 
    FROM 
        ganhos g 
    JOIN 
        Tipos t ON g.idTipo = t.idTipo 
    WHERE 
        g.idUsuario = ? 
    ORDER BY 
        g.valor DESC 
    LIMIT 10
";
$stmt_top_ganhos = mysqli_prepare($conn, $sql_top_ganhos);
if ($stmt_top_ganhos) {
    mysqli_stmt_bind_param($stmt_top_ganhos, "i", $user_id);
    mysqli_stmt_execute($stmt_top_ganhos);
    $top_ganhos = mysqli_fetch_all(mysqli_stmt_get_result($stmt_top_ganhos), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_top_ganhos);
}
function formatar_moeda($valor) {
    $abs_valor = abs($valor);
    $sinal = $valor < 0 ? '-' : '';
    $abreviacao = '';
    $divisor = 1;
    if ($abs_valor < 1000) {
        return $sinal . 'R$ ' . number_format($abs_valor, 2, ',', '.');
    }
    if ($abs_valor >= 1000000) {
        $divisor = 1000000;
        $abreviacao = 'M';
    } 
    elseif ($abs_valor >= 1000) {
        $divisor = 1000;
        $abreviacao = 'K';
    }

    $valor_abreviado = $abs_valor / $divisor;
    
    $valor_formatado = number_format($valor_abreviado, 1, '.', '');
    if (substr($valor_formatado, -2) === '.0') {
        $valor_final = substr($valor_formatado, 0, -2);
    } else {
        $valor_final = str_replace('.', ',', $valor_formatado);
    }

    return "{$sinal}R$ {$valor_final}{$abreviacao}";
}

function get_saldo_class($saldo) {
    if ($saldo > 0) return 'text-success';
    if ($saldo < 0) return 'text-danger';
    return 'text-secondary';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="pagina_inicial.css"> 
    <link rel="shortcut icon" href="myfinancefavicon.png" type="image/x-icon">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" style="color: white;" href="">MyFinance</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto d-flex w-50">
                    <li class="nav-item flex-fill text-center">
                      <button type="button" class="btn btn-cadastro text-white shadow w-100" data-bs-toggle="modal" data-bs-target="#transacaoModal">
                    <i class="fas fa-plus-circle"></i> Cadastrar Nova Transação
                       </button>
                    </li>
                    
                    <li class="nav-item ms-3 flex-fill text-center ">
                        <a  class="btn btn-cadastro text-white shadow w-100"  href="cadastro_tipo.php">Cadastrar tipos</a>
                    </li>
                         <li class="nav-item ms-3 flex-fill text-center ">
                        <a  class="btn btn-cadastro text-white "  href="logout.php"> <i class="fas fa-right-from-bracket"></i></a>
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
   <div class="container main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="text-center" style="color: var(--dark-blue-primary); font-weight: 700;">
             </i> Página inicial
            </h1>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <?php echo $mensagem; ?>

         
            <div class="row mb-5 g-4">
                
        
                <div class="col-md-4">
                    <div class="card shadow-lg p-3 card-saldo">
                        <div class="card-body text-center">
                            <h5 class="card-title text-secondary">SALDO TOTAL</h5>
                            <p class="text-saldo <?php echo get_saldo_class($saldo_total); ?>">
                                <?php echo formatar_moeda($saldo_total); ?>
                            </p>
                            <i class="fas fa-wallet fa-2x <?php echo get_saldo_class($saldo_total); ?>"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-lg p-3 card-ganho">
                        <div class="card-body text-center">
                            <h5 class="card-title text-secondary">GANHOS TOTAIS</h5>
                            <p class="text-saldo text-ganho">
                                <?php echo formatar_moeda($total_ganhos); ?>
                            </p>
                            <i class="fas fa-arrow-alt-circle-up fa-2x text-ganho"></i>
                        </div>
                    </div>
                </div>

                <!-- Total de Gastos -->
                <div class="col-md-4">
                    <div class="card shadow-lg p-3 card-gasto">
                        <div class="card-body text-center">
                            <h5 class="card-title text-secondary">GASTOS TOTAIS</h5>
                            <p class="text-saldo text-gasto">
                                <?php echo formatar_moeda($total_gastos); ?>
                            </p>
                            <i class="fas fa-arrow-alt-circle-down fa-2x text-gasto"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maiores Ganhos e Gastos (Tabelas) -->
            <div class="row g-4 mb-5">
                
                <!-- Top Ganhos -->
                <div class="col-md-6">
                    <div class="card shadow-sm p-3 bg-white">
                        <h4 class="card-title text-center text-ganho mb-3">
                            <i class="fas fa-trophy"></i> Maiores Ganhos
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-custom">
                                <thead class="table">
                                    <tr>
                                        <th>Transação</th>
                                        <th>Categoria</th>
                                        <th class="text-end">Valor (R$)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($top_ganhos)): ?>
                                        <?php foreach ($top_ganhos as $ganho): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ganho['nome']); ?></td>
                                                <td><?php echo htmlspecialchars($ganho['tipo_nome']); ?></td>
                                                <td class="text-end fw-bold text-ganho"><?php echo formatar_moeda($ganho['valor']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted">Nenhum ganho registrado ainda.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Top Gastos -->
                <div class="col-md-6">
                    <div class="card shadow-sm p-3 bg-white">
                        <h4 class="card-title text-center text-gasto mb-3">
                            <i class="fas fa-fire-alt"></i> Maiores Gastos
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-custom">
                                <thead class="table">
                                    <tr>
                                        <th>Transação</th>
                                        <th>Categoria</th>
                                        <th class="text-end">Valor (R$)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($top_gastos)): ?>
                                        <?php foreach ($top_gastos as $gasto): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($gasto['nome']); ?></td>
                                                <td><?php echo htmlspecialchars($gasto['tipo_nome']); ?></td>
                                                <td class="text-end fw-bold text-gasto"><?php echo formatar_moeda($gasto['valor']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted">Nenhum gasto registrado ainda.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            

        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>