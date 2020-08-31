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
		$tresc_zapytania = "SELECT * FROM logowania_uzytkownikow,users WHERE logowania_uzytkownikow.user=users.idu";
		
		if ($login!='wszyscy' && $login!='Użytkownik') //jeśli wybrano konkretnego użytkownika
		{
			$tresc_zapytania .= " AND users.login='$login'"; //dodaj odpowiedni fragment zapytania do bazy
			//nagłówek to informacja wyświetlana na samym szczycie tabeli
			$naglowek = '(<span style="color:red">'.$login.'</span>'; //wyświetl login na szczycie tabeli
		}
		else //jeśli nie wybrano konkretnego użytkownika, to wyświel na szczycie tabeli odpowiednią informację
		{
			$naglowek = '(<span style="color:red">wszyscy użytkownicy</span>';
		}
		if ($data!='wszystko' && $data!='Data') //jeśli wybrano konkretny przedział czasowy
		{
			//to dodaj odpowiedni fragment zapytania do bazy i odpowiednią informację do wyświetlanej na szczycie tabeli informacji
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
		else //jeśli nie wybrano konkretnego przedziału czasowego, to wyświetl na szczycie tabeli odpowiednią informację
		{
			$naglowek .= ',<span style="color:red">bez względu na datę</span>)';
		}
		
		$tresc_zapytania .= " ORDER BY logowania_uzytkownikow.idl DESC"; //zakończ zapytanie - w tym wypadku pokaż logowania wybranego użytkownika od ostaniego do pierwszego
		
//**************************************************** tworzenie danych do wyświetlenia na stronie ***************************************************
			
		echo '<div class="generuj_pdf"><a href="pdf_logowan.php" target="_blank" style="color:white;"> Generuj raport PDF </a></div>'; //wyświelt na stronie przycisk, dzięki któremu można wygenerować powstałe dane w postaci pliku PDF
		
		$do_pdf1 = '<h2 style="color:#000000"> Logowania'.$naglowek.' </h2>
					<table width="95%" align="center" border="1" bordercolor="#303030"  cellpadding="0" cellspacing="0"> 
					<tr>';
		echo $do_pdf1; //wyślij i wyświetl pierwszą część danych - informację na szczycie tabeli oraz jej (tabeli) zarys
		
		$zapytanie_o_dane = mysqli_query($connection, $tresc_zapytania);
		if (mysqli_num_rows($zapytanie_o_dane) >= 1)
		{
			//jeśli w bazie znajdują się żądane informacje, wyślij i wyświetl drugą część danych - nazwy kolumn
			$do_pdf2 = '<td style="position:sticky;top:73px;" width="30" align="center" bgcolor="e5e5e5">L.p.</td>
						<td style="position:sticky;top:73px;" width="40" align="center" bgcolor="e5e5e5">Login</td>
						<td style="position:sticky;top:73px;" width="40" align="center" bgcolor="e5e5e5">Typ</td>
						<td style="position:sticky;top:73px;" width="50" align="center" bgcolor="e5e5e5">IP</td>
						<td style="position:sticky;top:73px;" width="50" align="center" bgcolor="e5e5e5">Lokalizacja</td>
						<td style="position:sticky;top:73px;" width="50" align="center" bgcolor="e5e5e5">Państwo</td>
						<td style="position:sticky;top:73px;" width="50" align="center" bgcolor="e5e5e5">Region</td>
						<td style="position:sticky;top:73px;" width="50" align="center" bgcolor="e5e5e5">Miasto</td>
						<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">System i przeglądarka</td>
						<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Rozdzielczość ekranu i okna</td>
						<td style="position:sticky;top:73px;" width="50" align="center" bgcolor="e5e5e5">Data i godzina</td>
					</tr><tr>';
			
			echo<<<END
				$do_pdf2
END;
			
		}
		$do_pdf3 = '';
		for ($i=mysqli_num_rows($zapytanie_o_dane); $i>=1; $i--) //a następnie stwórz trzecią część danych - poszczególne rekordy spełniające żądane wytyczne
		{
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
			
			$to_co_wydrukowano_teraz = '<td width="30" align="center">'.$i.'</td>
										<td width="40" align="center">'.$login.'</td>
										<td width="40" align="center">'.$typ.'</td>
										<td width="50" align="center">'.$IP.'</td>
										<td width="50" align="center">'.$lokalizacja.'</td>
										<td width="50" align="center">'.$panstwo.'</td>
										<td width="50" align="center">'.$region.'</td>
										<td width="50" align="center">'.$miasto.'</td>
										<td width="60" align="center">'.$system_przegladarka.'</td>
										<td width="60" align="center">'.$rozdzielczosc.'</td>
										<td width="50" align="center">'.$data_godzina.'</td>
										</tr><tr>';
			echo<<<END
				$to_co_wydrukowano_teraz
END;
			//wyślij i wyświetl dane rekord po rekordzie
			$do_pdf3 .= $to_co_wydrukowano_teraz; //składaj, rekord po rekordzie, w trzecią część danych
		}
		$do_pdf4 = '</tr></table>'; //czwarta i ostatnia część danych to zamknięcie tabeli
		
		$_SESSION['dane'] = $do_pdf1.$do_pdf2.$do_pdf3.$do_pdf4; //złącz wszystkie dane w jedną całość - dzięki temu, będzie można wygenerować plik PDF
	
		echo $do_pdf4;	//wysłanie i wyświetlenie ostatniej części danych - koniec tabeli
?>