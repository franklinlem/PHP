<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados do Servidor</title>
</head>
<body>
    <h1>Dados do Servidor</h1>
    <p> 
        <?php
            $nome = "Franklin Martins";
            echo "O meu nome é $nome";
            phpinfo()
        ?>
        <?php echo "supertag PHP" ?>
        <? echo "short open tag" ?>
        <% "ASP tag" (nao funciona em alguns navegadores) %>
        <?= "short tag PHP" ?>
        <script language="php"> 
        //    echo "<p>outra forma de usar PHP (nao funciona em alguns navegadores)</p>"
        </script>
    </p>
    <p>Primeiro parágrafo de uma página com PHP.</p>
</body>
</html>