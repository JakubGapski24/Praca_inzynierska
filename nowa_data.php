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
		$data = $rezultat['data_ostatniego_laczenia'];
		
		$nowa_data = strtotime($data);
		echo $nowa_data; // przesłanie danych z powrotem do skryptu w JS
?>