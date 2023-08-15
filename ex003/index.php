<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipos primitivos em PHP</title>
</head>
<body>
    <h1>Teste de tipos primitivos em PHP</h1>
    <?php 
        //0x = hexadecimal 0b = binário 0 = octal
        $num= 010;
        echo "O valor da variável é $num";
        $v = 34.5;
        var_dump($v); //informa a variavel e o tipo
        $x = 3e2;
        var_dump($x);
        $a = (integer)5e2; //coerçao
        var_dump($a);
        $teste = (int) "680";
        var_dump($teste);
        $casado = true;
        var_dump($casado);
        echo "Fulano é $casado";
    ?>
</body>
</html>