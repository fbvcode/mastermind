<?php

/**
 * Programa: Mastermind (Muertos y Heridos)
 * Tarea: Unidad 4 DWES
 * Autor: Fátima Brígido Ventura
 */
//Iniciamos la sesion antes de escribir cualquier código HTML
session_start();

// Si el usuario ha pulsado el botón de "Nueva partida" destruímos lo que había para empezar de cero
if (isset($_POST['reiniciar']) || isset($_POST['finalizar'])) {
    session_destroy(); // Borra todos los datos de la sesión
    header("Location: " . $_SERVER['PHP_SELF']); // Recarga la página limpia
    exit;
}

//Comprobamos si el juego ya ha empezado, es decir, si tiene creada la variable 'numero_secreto'
if (!isset($_SESSION['numero_secreto'])) {
    //Si no está creada la variable 'numero_secreto', entonces la creamos    
    $numero = "";

    //Mientras que el número secreto no tenga 4 dígitos
    while (strlen($numero) < 4) {
        //Creamos una cifra aleatoria entre el 0 y el 9 (incluídos)
        $cifra = random_int(0, 9);

        //Comprobamos si esa cifra ya está en el número secreto, ya que no se pueden repetir
        if (!str_contains($numero, (string) $cifra)) {
            //Si la cifra no está ya en el número secreto, la añadimos
            $numero .= $cifra;
        }
    }
    //Una vez que el número secreto alcanza 4 cifras, el bucle deja de crear más cifras y guardamos el número secreto en la sesion
    $_SESSION['numero_secreto'] = $numero;

    //A la vez que creamos el numero secreto, reseteamos el número de intentos
    $intento_actual = 0;

    //También inicializamos el historial y lo guardamos en la sesion
    $_SESSION['historial'] = [];

    //Guardamos la hora de inicio para mostrarla en el mensaje de record
    $_SESSION['hora_inicio'] = new DateTime(); //hay que guardarlo como objeto para lueog usar interval
}



//Comprobamos si se ha pulsado el boton enviar
if (isset($_POST['enviar'])) {
    //Recogemos el array con las cifras que ha introducido el usuario
    $intento_array = $_POST['intento'];

    //Unimos las cifras de ese array en un solo string
    $intento_string = implode("", $intento_array);

    //Inicializamos los muertos y heridos
    $muertos = 0;
    $heridos = 0;

    //Bucle que se repetirá 4 veces, una por cada dígito
    for ($d = 0; $d < 4; $d++) {
        //Comparamos el dígito introducido por el usuario con el dígito en la misma posicion del número secreto
        if ($intento_string[$d] === $_SESSION['numero_secreto'][$d]) {
            //Si coincide numero y posicion, sumamos un muerto
            $muertos++;
        } elseif (str_contains($_SESSION['numero_secreto'], $intento_string[$d])) {
            //Si no es muerto, miramos si puede ser un herido y, si lo es, sumamos un herido
            $heridos++;
        }
    }

    $intento_actual = count($_SESSION['historial']) + 1;

    $resultado = "$intento_actual. Jugada: $intento_string - Muertos: $muertos - Heridos: $heridos";
    $_SESSION['historial'][] = $resultado;

    if ($muertos === 4) {
        //Guardamos también la hora de fin para el mensaje de record
        $_SESSION['hora_fin'] = new DateTime();

        //Diferencia entre hora inicio y hora fin
        $_SESSION['intervalo'] = $_SESSION['hora_inicio']->diff($_SESSION['hora_fin']);

        $duracion = $_SESSION['intervalo']->format('%H:%I:%S');


        //Lo he tenido que guardar en la sesión porque me daba problemas al recargarse la pagina
        $_SESSION['mensaje_victoria'] = "Has adivinado el número secreto.";

        //Si no existe record o batimos record:
        if (!isset($_COOKIE['record_intentos']) || $intento_actual < $_COOKIE['record_intentos']) {
            setcookie('record_intentos', $intento_actual, time() + (3600 * 24 * 30));

            //Creamos la fecha del record y la guardamos también en las cookies
            $fecha = date("d/m/Y");
            setcookie('record_fecha', $fecha, time() + (3600 * 24 * 30));

            $_SESSION['mensaje_record'] = "Enhorabuena, NUEVO RECORD! <br>" .
                "Has empezado a las " . $_SESSION['hora_inicio']->format('H:i:s') .
                " y finalizado a las " . $_SESSION['hora_fin']->format('H:i:s') .
                "<br>Has acertado en $intento_actual jugadas con una duración de $duracion";
        }
    }

    session_write_close(); // Asegura que los mensajes se guarden antes de recargar
    //Si recargamos la página no queremos que se reenvíe de nuevo la última jugada
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <link href="estilos.css" rel="stylesheet" type="text/css">
    <meta charset="UTF-8">
    <title>Mastermind</title>

</head>

<body>
    <header>
        <div class="divrecord">
            <?php if (isset($_COOKIE['record_intentos']) && isset($_COOKIE['record_fecha'])): ?>
                <p>Récord actual: <?php echo $_COOKIE['record_intentos']; ?> jugadas. </p>
                <p>Obtenido el: <?php echo $_COOKIE['record_fecha']; ?></p>
            <?php else: ?>
                <p>Aún no hay record.</p>
            <?php endif; ?>
        </div>

        <p>Número secreto: <?php echo $_SESSION['numero_secreto']; ?> </p>
    </header>

    <h1>MASTERMIND JUEGO DE LOS MUERTOS Y HERIDOS </h1>

    <?php if (isset($_SESSION['mensaje_record'])): ?>
        <!-- Si el usuario además ha batido record, mostramos el mensaje correspondiente -->
        <h2 class="record">
            <?php echo $_SESSION['mensaje_record']; ?>
        </h2>
        <?php unset($_SESSION['mensaje_record']); //Lo borramos para que desaparezca al recargar 
        ?>
        <!-- Si el usuario ha acertado, mostramos mensaje de victoria -->
    <?php elseif (isset($_SESSION['mensaje_victoria'])): ?>
        <h2 class="victoria">
            <?php echo $_SESSION['mensaje_victoria']; ?>
        </h2>
        <?php unset($_SESSION['mensaje_victoria']); //Lo borramos para que desaparezca al recargar 
        ?>


    <?php endif; ?>



    <div class="divFormulario">
        <form method="POST">
            <label>Introduce Número: </label>

            <input type="text" name="intento[]" maxlength="1" required>
            <input type="text" name="intento[]" maxlength="1" required>
            <input type="text" name="intento[]" maxlength="1" required>
            <input type="text" name="intento[]" maxlength="1" required>

            <button type="submit" name="enviar">Enviar</button>

        </form>
        <!--<p>Numero introducido: <?php echo $intento_string; ?></p> Esto era para depurar-->
    </div>
    <?php if (!empty($_SESSION['historial'])) : ?>
        <div class="registroJugadas">
            <h2>REGISTRO DE JUGADAS</h2>
            <ul>
                <?php foreach ($_SESSION['historial'] as $jugada) : ?>
                    <li><?php echo $jugada; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="POST" class="nuevaPartida">
        <button type="submit" name="reiniciar">Nueva Partida</button>
        <button type="submit" name="finalizar">Salir</button>
    </form>
</body>
<footer>
    <p>Fátima Brígido Ventura</p>
</footer>

</html>