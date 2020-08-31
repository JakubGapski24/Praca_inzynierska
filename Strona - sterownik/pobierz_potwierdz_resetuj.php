	<?php
				//pobierz dane bazy, z którą chcesz się połączyć
				require_once "connect.php";
				$connection = mysqli_connect($host, $user, $password);
				mysqli_query($connection, "SET CHARSET utf8");
				mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
				mysqli_select_db($connection, $db);
				
				//pobierz dane o potwierdzeniu i resecie
				$SQL1 = mysqli_query($connection,"SELECT potwierdz FROM stany_wyprowadzen ORDER BY id DESC LIMIT 1");
                $WYNIK1 = mysqli_fetch_assoc($SQL1);
				$potwierdz = $WYNIK1['potwierdz'];
				
				$SQL2 = mysqli_query($connection,"SELECT reset FROM stany_wyprowadzen ORDER BY id DESC LIMIT 1");
                $WYNIK2 = mysqli_fetch_assoc($SQL2);
				$reset = $WYNIK2['reset'];
				
				$jeden = 1; //zmienna wprowadzona po to, by uniknąć problemów z odczytem ciągu znaków, np. gdy 'potwierdz' będzie ustawione na '0' (czyli wyłącz potwierdzenie), a reset na '1' (czyli zresetuj system)
				
				echo '<'.$jeden.$potwierdz.$reset.'>'; //przekaż otrzymane dane do sterownika (Arduino odczytuje znaki między '<' i '>')
?>
						
		
		