<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="cadastro.css">
    <link rel="icon" type="image/png" href="myfinancefavicon.png">
</head>
<body>
  <nav class="navbar navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">MyFinance</a>
    </nav>

<div class="card card-myfinance">
    <div class="card-body">
        <h3 class="card-title text-center">Cadastro</h3>
        

        <form action="script_cadastro.php" method="POST">
            
            <div class="mb-3">
                <label for="username" class="form-label">Nome de Usuário</label>
                <input type="text" class="form-control" id="username" name="nome" placeholder="Seu nome de usuário" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="nome@exemplo.com" required>
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Mínimo 8 caracteres" required>
            </div>

            <div class="mb-4">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="(XX) XXXXX-XXXX">
            </div>

             <div class="mb-4">
                <label for="data_nascimento" class="form-label">Data de nascimento</label>
                <input type="date" class="form-control" id="telefone" name="data_nascimento" placeholder="DD/MM/YYYY">
            </div>

            <button type="submit" class="btn btn-register w-100">Criar Minha Conta</button>
            
            <div class="mt-3 text-center">
                <small class="text-muted">Já tem uma conta? <a href="#" class="text-decoration-none" style="color: var(--dark-blue-primary);">Faça login aqui</a></small>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>