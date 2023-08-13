<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP</title>
</head>
<body>
    <h1>Exemplo de PHP</h1>
        <?php 
            date_default_timezone_set("America/Sao_Paulo");
            echo "Hoje é dia " . date("d/m/Y D");
            echo " e a hora é " . date("G:i:s");
            $sobrenome = "Martins";
	        $nome = "Franklin";
	        const PAIS = "Brasil";
	        echo" Muito prazer, $nome $sobrenome! Você mora no " . PAIS;
            #COMENTARIO EM PHP
            //COMENTARIO EM PHP
            $nomeCompletoCliente = "CamelCase";
            $telefone_contato_fornecedor = "Snake Case";
            $nomecursosuperior = "Tudo minusculo";
            $_TX = 1234; //nome de variaveis podem ter simbolos
            $£abc123 = 5678;
            print $nomeCompletoCliente;
            print $telefone_contato_fornecedor;
            print $_TX;
            print $£abc123;
            
        ?>
    <p>Primeiro parágrafo de uma página com PHP.</p>
</body>
</html>