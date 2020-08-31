<?php
		session_start();
		//pobierz dane bazy, z którą chcesz się połączyć
		require_once "connect.php";
		$connection = mysqli_connect($host, $user, $password);
		mysqli_query($connection, "SET CHARSET utf8");
		mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
		mysqli_select_db($connection, $db);
		
		//odbierz wysłany dzięki AJAX-owi login
		$login = $_REQUEST['konto'];
		
		//pobierz odpowiednie dane
		$zapytanie_o_dane = mysqli_query($connection, "SELECT * FROM users WHERE login='$login'");
		$i = mysqli_fetch_assoc($zapytanie_o_dane);
			
		$idu = $i['idu'];
		$login = $i['login'];
		$email = $i['email'];
		
		$respond = 'NIE'; //informacja o usunięciu wybranego konta
		
		$usun = mysqli_query($connection, "DELETE FROM users WHERE login='$login'"); //usuń wybrane konto
		$usun1 = mysqli_query($connection, "DELETE FROM logowania_uzytkownikow WHERE user='$idu'"); //usuń wszystkie logowania usuniętego użytkownika
		
		if ($usun && $usun1) //jeśli obie operacje usuwania zakończone sukcesem, to...
		{
			$respond = 'TAK';
			
			$tytul = "Usunięcie konta - portal SAP";
			$wiadomosc = 'Twoje konto (login: '.$login.') zostało usunięte przez administratora.';
								
			mail($email, $tytul, $wiadomosc); //wyślij odpowiedni komunikat na adres e-mail usuwanego użytkownika
			
			$_SESSION['usuniety_user'] = $login; //zapamiętaj login usuwanego użytkownika, by móc poprawnie wyświetlić komunikat o usunięciu jego konta
			$_SESSION['CZY_USUNIETO'] = true; //potwierdzenie usunięcia konta
		}
		
		echo $respond; // przesłanie danych z powrotem do skryptu w JS
?>