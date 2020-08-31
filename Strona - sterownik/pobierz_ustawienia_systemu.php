	<?php
				//pobierz dane bazy, z którą chcesz się połączyć
				require_once "connect.php";
				$connection = mysqli_connect($host, $user, $password);
				mysqli_query($connection, "SET CHARSET utf8");
				mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
				mysqli_select_db($connection, $db);
				
				//pobierz dane o uszkodzonych czujnikach
				$SQL1 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_plomienia'");
				$WYNIK1 = mysqli_fetch_assoc($SQL1);
				$czujka_plomienia = $WYNIK1['czy_uszkodzona'];
		
				$SQL2 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_dymu'");
				$WYNIK2 = mysqli_fetch_assoc($SQL2);
				$czujka_dymu = $WYNIK2['czy_uszkodzona'];
		
				$SQL3 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='rop'");
				$WYNIK3 = mysqli_fetch_assoc($SQL3);
				$rop = $WYNIK3['czy_uszkodzona'];
		
				$SQL4 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_temperatury'");
				$WYNIK4 = mysqli_fetch_assoc($SQL4);
				$czujka_temperatury = $WYNIK4['czy_uszkodzona'];
				
				
				//pobierz pierwotnie ustawiony czas do wystąpienia alarmu drugiego stopnia
				$SQL5 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_plomienia'");
				$WYNIK5 = mysqli_fetch_assoc($SQL5);
				$czas = $WYNIK5['czas_do_alarmu2'];
				
				echo '<'.$czas.$czujka_plomienia.$czujka_dymu.$rop.$czujka_temperatury.'>'; //przekaż otrzymane dane do sterownika (Arduino odczytuje znaki między '<' i '>')
?>
						
		
		