<?php
		session_start();
		//pobierz dane bazy, z którą chcesz się połączyć
		require_once "connect.php";
		$connection = mysqli_connect($host, $user, $password);
		mysqli_query($connection, "SET CHARSET utf8");
		mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
		mysqli_select_db($connection, $db);
		
		//odbierz wysłane dzięki AJAX'owi dane
		$login = $_REQUEST['q1'];
		$data = $_REQUEST['q2'];
		
		//stwórz odpowiednie zapytanie do bazy, zgodnie z otrzymanymi danymi
		$tresc_zapytania = "SELECT * FROM logowania_uzytkownikow,users WHERE users.login='$login' AND logowania_uzytkownikow.user=users.idu";
		$naglowek = '(<span style="color:red">'.$login.'</span>'; //nagłówek to informacja wyświetlana na samym szczycie tabeli
		
		if ($data!='wszystko' && $data!='Data')
		{
			//dodaj odpowiedni fragment zapytania do bazy i odpowiednią informację do wyświetlanej na szczycie tabeli informacji
			if ($data=='tydzien')
			{
				$tresc_zapytania .= " AND DATEDIFF(NOW(),logowania_uzytkownikow.data_godzina) <= 7";
				$naglowek .= ',<span style="color:red">ostatni tydzień</span>)';
			}
			else if ($data=='dwa_tygodnie')
			{
				$tresc_zapytania .= " AND DATEDIFF(NOW(),logowania_uzytkownikow.data_godzina) <= 14";
				$naglowek .= ',<span style="color:red">ostatnie dwa tygodnie</span>)';
			}
			else if ($data=='miesiac')
			{
				$tresc_zapytania .= " AND DATEDIFF(NOW(),logowania_uzytkownikow.data_godzina) <= 30";
				$naglowek .= ',<span style="color:red">ostatni miesiąc</span>)';
			}
			else if ($data=='trzy_miesiace')
			{
				$tresc_zapytania .= " AND DATEDIFF(NOW(),logowania_uzytkownikow.data_godzina) <= 90";
				$naglowek .= ',<span style="color:red">ostatnie trzy miesiące</span>)';
			}
			else if ($data=='pol_roku')
			{
				$tresc_zapytania .= " AND DATEDIFF(NOW(),logowania_uzytkownikow.data_godzina) <= 180";
				$naglowek .= ',<span style="color:red">ostatnie pół roku</span>)';
			}
			else if ($data=='rok')
			{
				$tresc_zapytania .= " AND DATEDIFF(NOW(),logowania_uzytkownikow.data_godzina) <= 365";
				$naglowek .= ',<span style="color:red">ostatni rok</span>)';
			}
		}
		
		$tresc_zapytania .= " ORDER BY logowania_uzytkownikow.idl DESC"; //zakończ zapytanie - w tym wypadku pokaż logowania wybranego użytkownika od ostaniego do pierwszego
			
//**************************************************** tworzenie danych do wyświetlenia na stronie ***************************************************
		
		//wyślij i wyświetl pierwszą część danych - informację na szczycie tabeli oraz jej (tabeli) zarys
		echo '<h2 style="color:#000000"> Logowania'.$naglowek.' </h2>
			  <table width="95%" align="center" border="1" bordercolor="#303030"  cellpadding="0" cellspacing="0"> 
					<tr>';
		
		$zapytanie_o_dane = mysqli_query($connection, $tresc_zapytania);
		if (mysqli_num_rows($zapytanie_o_dane) >= 1)
		{
			//jeśli w bazie znajdują się żądane informacje, wyślij i wyświetl drugą część danych - nazwy kolumn
			echo       '<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">IP</td>
						<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Lokalizacja</td>
						<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Państwo</td>
						<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Region</td>
						<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Miasto</td>
						<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">System i przeglądarka</td>
						<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Rozdzielczość ekranu i okna</td>
						<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Data i godzina</td>
					</tr><tr>';		
		}
		
		for ($i=1; $i<=mysqli_num_rows($zapytanie_o_dane); $i++)
		{
			//a następnie wyślij i wyświetl trzecią część danych - poszczególne rekordy spełniające żądane wytyczne
			$wynik = mysqli_fetch_assoc($zapytanie_o_dane);
			$login = $wynik['login'];
			$typ = $wynik['typ'];
			$IP = $wynik['IP'];
			$lokalizacja = $wynik['lokalizacja'];
			$panstwo = $wynik['panstwo'];
			$region = $wynik['region'];
			$miasto = $wynik['miasto'];
			$system_przegladarka = $wynik['system_przegladarka'];
			$rozdzielczosc = $wynik['rozdzielczosc'];
			$data_godzina = $wynik['data_godzina'];
			
								  echo '<td width="60" align="center">'.$IP.'</td>
										<td width="60" align="center">'.$lokalizacja.'</td>
										<td width="60" align="center">'.$panstwo.'</td>
										<td width="60" align="center">'.$region.'</td>
										<td width="60" align="center">'.$miasto.'</td>
										<td width="60" align="center">'.$system_przegladarka.'</td>
										<td width="60" align="center">'.$rozdzielczosc.'</td>
										<td width="60" align="center">'.$data_godzina.'</td>
										</tr><tr>';
		}
		echo '</tr></table>'; //wysłanie i wyświetlenie ostatniej części danych - koniec tabeli
?>