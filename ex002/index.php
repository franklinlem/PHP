<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP</title>
</head>
<body>
    <h1>Exemplo da PHP</h1>
        <?php 
            date_default_timezone_set("America/Sao_Paulo");
            echo "Hoje é dia " . date("d/m/Y D");
            echo " e a hora é " . date("G:i:s")
        ?>
    <p>Primeiro parágrafo de uma página com PHP.</p>
</body>
</html>