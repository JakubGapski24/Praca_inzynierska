<?php
		session_start();
		//pobierz dane bazy, z którą chcesz się połączyć
		require_once "connect.php";
		$connection = mysqli_connect($host, $user, $password);
		mysqli_query($connection, "SET CHARSET utf8");
		mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
		mysqli_select_db($connection, $db);
		
		//odbierz przysłane dzięki AJAX-owi dane
		$info = $_REQUEST['info']; //numer elementu w bazie
		$i = $_REQUEST['ile']; //informacja, którą informację o elemencie (nazwę czy opis) zwrócić w tym momencie z powrotem do skryptu wywołującego
		
		//pobierz żądane informacje
		$tresc_zapytania = "SELECT * FROM elementy_systemu WHERE ide='$info'";
				
		$zapytanie_o_dane = mysqli_query($connection, $tresc_zapytania);
			
		$wynik = mysqli_fetch_assoc($zapytanie_o_dane);
		
		$nazwa = $wynik['nazwa'];
		$opis = $wynik['opis'];
		
		if ($i == 1) //w tym momencie zwróć nazwę elementu
		{
			echo $nazwa;
		}
		else if ($i == 2) //w tym momenie zwróć opis elementu
		{
			echo $opis;
		}
?>