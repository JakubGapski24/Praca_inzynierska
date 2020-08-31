<?php
		//pobierz dane bazy, z którą chcesz się połączyć
		require_once "connect.php";
		$connection = mysqli_connect($host, $user, $password);
		mysqli_query($connection, "SET CHARSET utf8");
		mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
		mysqli_select_db($connection, $db);
		//pobierz datę ostatniego łączenia sterownika z serwerem
		$zapytanie = mysqli_query($connection, "SELECT * FROM stany_wyprowadzen ORDER BY id DESC LIMIT 1");
		$rezultat = mysqli_fetch_assoc($zapytanie);
		$data_ostatniego_laczenia = $rezultat['data_ostatniego_laczenia'];
		$roznica = strtotime(date("Y-m-d H:i:s")) - strtotime($data_ostatniego_laczenia); //ponowne obliczenie różnicy między wskazanymi datami (aktualną i ostatniego łączenia sterownika z serwerem)
		
		echo $roznica; // przesłanie danych z powrotem do skryptu w JS
?>