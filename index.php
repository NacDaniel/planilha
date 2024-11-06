<?php
require __DIR__ . '/tudo.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    new tudo();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
     <form action="" method="POST">
        <button>Atualizar planilha</button>
     </form>
</body>
</html>
