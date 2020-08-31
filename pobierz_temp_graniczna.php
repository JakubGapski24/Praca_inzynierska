<?php
				//pobierz dane bazy, z którą chcesz się połączyć
				require_once "connect.php";
				$connection = mysqli_connect($host, $user, $password);
				mysqli_query($connection, "SET CHARSET utf8");
				mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
				mysqli_select_db($connection, $db);
				
				$SQL1 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_plomienia'");
				$WYNIK1 = mysqli_fetch_assoc($SQL1);
				$temperatura = $WYNIK1['temperatura_graniczna']; //pobierz wartość ustawionej temperatury granicznej
				
				echo '<'.$temperatura.'>'; //przekaż ją do sterownika (Arduino odczytuje znaki między '<' i '>')
?>				