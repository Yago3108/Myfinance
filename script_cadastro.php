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
        <?php 
        include "conexao.php";

        $nome = $_POST["nome"];
        $email = $_POST["email"];
        $senha = $_POST["senha"];
        $telefone = $_POST["telefone"];
        $data_nascimento = $_POST['data_nascimento'];

        $sql= "INSERT INTO `usuarios`( `nome`, `email`, `telefone`, `senha`, `data_nascimento`)
         VALUES ('$nome','$email','$telefone','$senha','$data_nascimento')";
         if(mysqli_query($conn, $sql)){
            echo "$nome cadastrado com sucesso!";
         }else{
            echo "erro não foi cadastrado";
         }
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>