<?php
		//pobierz dane bazy, z którą chcesz się połączyć
		require_once "connect.php";
		$connection = mysqli_connect($host, $user, $password);
		mysqli_query($connection, "SET CHARSET utf8");
		mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
		mysqli_select_db($connection, $db);

		//dzięki AJAX-owi, pobierz dane o wymiarach ekranu
		$screen_width = $_REQUEST['q1'];
		$screen_height = $_REQUEST['q2'];
		$window_inner_width = $_REQUEST['q3'];
		$window_inner_height = $_REQUEST['q4'];
		$rozdzielczosc = $screen_width.'x'.$screen_height.' Inner: '.$window_inner_width.'x'.$window_inner_height; //zapisz pobrane dane w postaci jednej informacji o rozdzielczości
		mysqli_query($connection, "UPDATE logowania_uzytkownikow SET rozdzielczosc='$rozdzielczosc', data_godzina=NOW() ORDER BY idl DESC LIMIT 1"); //wstaw dane o wykrytej rozdzielczości ekranu do bazy danych, dla odpowiedniego użytkownika (jest to tak skonstruowane, że dane o rozdzielczości ekranu są ostatnimi zbieranymi o użytkowniku informacjami, więc aktualizowane są odpowiednie pola w ostatnim rekordzie bazy)

?>