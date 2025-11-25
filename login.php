<?php
session_start();

require_once "conexao.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $senha = mysqli_real_escape_string($conn, $_POST["senha"]);
    
    // --- 3. PRIMEIRA CONSULTA: Verifica a existência do usuário ---
    // Consulta para verificar se a combinação email/senha existe
    $sql = "SELECT id, istipocadastrado FROM usuarios WHERE email = '$email' AND senha = '$senha'";
    $resultado = mysqli_query($conn, $sql);
    
    if ($resultado) {
        $usuario = mysqli_fetch_assoc($resultado);
        
        if ($usuario) { 
            
 
            $_SESSION['logado'] = true;
            $_SESSION['user_id'] = $usuario['id']; 
            
            if($usuario["istipocadastrado"] == 1){
                header("Location: cadastro_tipo.php");
                exit(); 
            } else {
                header("Location: pagina_inicial.php");
                exit(); 
            }
        
        } else {
            echo "<script>alert('Email ou senha inválidos.'); window.location.href = 'index.html';</script>";
        }
    } else {
        die("Erro fatal na consulta: " . mysqli_error($conn));
    }
    


}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MyFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="cadastro.css">
    <link rel="icon" type="image/png" href="myfinancefavicon.png">
</head>
<body>
    <nav class="navbar navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">MyFinance</a>
        </div>
    </nav>

    <div class="card card-myfinance mx-auto mt-5" style="max-width: 400px;">
        <div class="card-body">
            <h3 class="card-title text-center">Login</h3>
            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Seu email" required>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" required>
                </div>

                <button type="submit" class="btn btn-register w-100 btn-primary">Fazer Login</button>
                
                <div class="mt-3 text-center">
                    <small class="text-muted">Não tem uma conta? <a href="cadastro.php" class="text-decoration-none" style="color: #0D47A1;">Faça seu cadastro aqui</a></small>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>