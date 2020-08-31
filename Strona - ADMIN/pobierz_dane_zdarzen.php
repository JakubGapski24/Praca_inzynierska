<?php
		session_start();
		//pobierz dane bazy, z którą chcesz się połączyć
		require_once "connect.php";
		$connection = mysqli_connect($host, $user, $password);
		mysqli_query($connection, "SET CHARSET utf8");
		mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
		mysqli_select_db($connection, $db);
		
		//odbierz wysłane dzięki AJAX'owi dane
		$zdarzenie = $_REQUEST['q1'];
		$data = $_REQUEST['q2'];
		
		//dokładnie jeden czujnik musi wskazywać alarm - szukany rekord, który "zaczyna" alarm pierwszego stopnia
		$warunek_o_czujki = "((czujnik_plomienia=1 AND czujnik_dymu!=1 AND rop!=1 AND czujnik_temperatury!=1)
																	OR
							 (czujnik_plomienia!=1 AND czujnik_dymu=1 AND rop!=1 AND czujnik_temperatury!=1)
																	OR
							 (czujnik_plomienia!=1 AND czujnik_dymu!=1 AND rop=1 AND czujnik_temperatury!=1)
																	OR
							 (czujnik_plomienia!=1 AND czujnik_dymu!=1 AND rop!=1 AND czujnik_temperatury=1))";
		
		//stwórz odpowiednie zapytanie, w zależności od wybranych danych
		$tresc_zapytania = "SELECT * FROM stany_wyprowadzen WHERE ";
		
		if ($zdarzenie=='reset') //jeżeli żądanym zdarzeniem są resety systemu, to odpowiednio zmodyfikuj treść zapytania i informację, która będzie wyświetlona na szczycie tabeli
		{
			$tresc_zapytania .= "(reset=1)";
			$naglowek = '(<span style="color:red">Reset systemu</span>';
		}
		else if ($zdarzenie=='potwierdz') //jeżeli żądanym zdarzeniem są potwierdzenia alarmu, to odpowiednio zmodyfikuj treść zapytania i informację, która będzie wyświetlona na szczycie tabeli
		{
			$tresc_zapytania .= "(reset=0 AND potwierdz=1)";
			$naglowek = '(<span style="color:red">Potwierdzenie alarmu</span>';
		}
		else if ($zdarzenie=='alarm_pierwszego_stopnia') //jeżeli żądanym zdarzeniem są alarmy pierwszego stopnia, to odpowiednio zmodyfikuj treść zapytania i informację, która będzie wyświetlona na szczycie tabeli
		{
			$tresc_zapytania .= "(pozar1=1 AND $warunek_o_czujki AND reset=0)";
			$naglowek = '(<span style="color:red">Alarm pierwszego stopnia</span>';
		}
		else if ($zdarzenie=='alarm_drugiego_stopnia') //jeżeli żądanym zdarzeniem są alarmy drugiego stopnia, to odpowiednio zmodyfikuj treść zapytania i informację, która będzie wyświetlona na szczycie tabeli
		{
			$tresc_zapytania .= "(pozar2=1 AND reset=0)";
			$naglowek = '(<span style="color:red">Alarm drugiego stopnia</span>';
		}
		
		//dodaj odpowiedni fragment zapytania do bazy i odpowiednią informację do wyświetlanej na szczycie tabeli informacji, w zależności od żądanego przedziału czasowego
		if ($data=='tydzien')
		{
			$tresc_zapytania .= " AND DATEDIFF(NOW(),datagodzina) <= 7";
			$naglowek .= ',<span style="color:red">ostatni tydzień</span>)';
		}
		else if ($data=='dwa_tygodnie')
		{
			$tresc_zapytania .= " AND DATEDIFF(NOW(),datagodzina) <= 14";
			$naglowek .= ',<span style="color:red">ostatnie dwa tygodnie</span>)';
		}
		else if ($data=='miesiac')
		{
			$tresc_zapytania .= " AND DATEDIFF(NOW(),datagodzina) <= 30";
			$naglowek .= ',<span style="color:red">ostatni miesiąc</span>)';
		}
		else if ($data=='trzy_miesiace')
		{
			$tresc_zapytania .= " AND DATEDIFF(NOW(),datagodzina) <= 90";
			$naglowek .= ',<span style="color:red">ostatnie trzy miesiące</span>)';
		}
		else if ($data=='pol_roku')
		{
			$tresc_zapytania .= " AND DATEDIFF(NOW(),datagodzina) <= 180";
			$naglowek .= ',<span style="color:red">ostatnie pół roku</span>)';
		}
		else if ($data=='rok')
		{
			$tresc_zapytania .= " AND DATEDIFF(NOW(),datagodzina) <= 365";
			$naglowek .= ',<span style="color:red">ostatni rok</span>)';
		}
		
		$tresc_zapytania .= " ORDER BY id DESC"; //zakończ zapytanie - w tym wypadku pokaż zdarzenia od ostaniego do pierwszego
			
//**************************************************** tworzenie danych do wyświetlenia na stronie ***************************************************
		
		echo '<div class="generuj_pdf"><a href="pdf_zdarzen.php" target="_blank" style="color:white;"> Generuj raport PDF </a></div>'; //wyświetl na stronie przycisk, dzięki któremu można wygenerować powstałe dane w postaci pliku PDF
		
		$do_pdf1 = '<h2 style="color:#000000"> Zdarzenia'.$naglowek.' </h2>
					<table width="95%" align="center" border="1" bordercolor="#303030"  cellpadding="0" cellspacing="0"> 
					<tr>';
		echo $do_pdf1; //wyślij i wyświetl pierwszą część danych - informację na szczycie tabeli oraz jej (tabeli) zarys
		
		$zapytanie_o_dane = mysqli_query($connection, $tresc_zapytania);
		
		//stwórz drugą część danych - w zależności od tego, jakie dane mają zostać pokazane
		$do_pdf2 = '';
		if ($zdarzenie == 'reset') //jeżeli żądanym zdarzeniem jest resetowanie systemu, to...
		{	
			if (mysqli_num_rows($zapytanie_o_dane) >= 1) 
			{
				//jeśli w bazie znajdują się żądane informacje, wyślij i wyświetl drugą część danych - nazwy kolumn
				$do_pdf2 .= '<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">L.p.</td>
							 <td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Data i godzina zresetowania</td>
					</tr><tr>';
			
				echo<<<END
					$do_pdf2
END;
			}
			
			$do_pdf3 = '';
			for ($i=mysqli_num_rows($zapytanie_o_dane); $i>=1; $i--) //a następnie stwórz trzecią część danych - poszczególne rekordy spełniające żądane wytyczne
			{
				$wynik = mysqli_fetch_assoc($zapytanie_o_dane);
				$data = $wynik['datagodzina'];
				$to_co_wydrukowano_teraz = '<td width="60" align="center">'.$i.'</td>
											<td width="60" align="center">'.$data.'</td>
											</tr><tr>';
				echo<<<END
					$to_co_wydrukowano_teraz
END;
				//wyślij i wyświetl dane rekord po rekordzie
				$do_pdf3 .= $to_co_wydrukowano_teraz; //składaj, rekord po rekordzie, w trzecią część danych
			}
			$do_pdf4 = '</tr></table>'; //czwarta i ostatnia część danych to zamknięcie tabeli
		}
		else if ($zdarzenie == 'potwierdz') //jeżeli żądanym zdarzeniem jest potwierdzenie alarmu, to...
		{
			if (mysqli_num_rows($zapytanie_o_dane) >= 1) 
			{
				//jeśli w bazie znajdują się żądane informacje, wyślij i wyświetl drugą część danych - nazwy kolumn
				$do_pdf2 .= '<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">L.p.</td>
							 <td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Rodzaj potwierdzonego alarmu</td>
							 <td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Data i godzina potwierdzenia</td>
					</tr><tr>';
			
				echo<<<END
					$do_pdf2
END;
			}
			
			$do_pdf3 = '';
			for ($i=mysqli_num_rows($zapytanie_o_dane); $i>=1; $i--) //a następnie stwórz trzecią część danych - poszczególne rekordy spełniające żądane wytyczne
			{
				$wynik = mysqli_fetch_assoc($zapytanie_o_dane);
				$aktualne_id = $wynik['id'];
				$data = $wynik['datagodzina'];
				$sprawdz_ktory_alarm_zostal_potwierdzony = mysqli_query($connection, "SELECT * FROM stany_wyprowadzen WHERE id='$aktualne_id'-1"); //sprawdzenie poprzedniego rekordu w bazie, w celu ustalenia, który rodzaj alarmu został potwierdzony - w poprzednim rekordzie znajduje się informacja, który rodzaj alarmu był wskazywany
				$rezultat = mysqli_fetch_assoc($sprawdz_ktory_alarm_zostal_potwierdzony);
				$alarm1 = $rezultat['pozar1'];
				$alarm2 = $rezultat['pozar2'];
				
				if ($alarm1 == 1)
				{
					$rodzaj_potwierdzonego_alarmu = 'Alarm pierwszego stopnia';
				}
				
				if ($alarm2 == 1)
				{
					$rodzaj_potwierdzonego_alarmu = 'Alarm drugiego stopnia';
				}
				
				$to_co_wydrukowano_teraz = '<td width="60" align="center">'.$i.'</td>
											<td width="60" align="center">'.$rodzaj_potwierdzonego_alarmu.'</td>
											<td width="60" align="center">'.$data.'</td>
											</tr><tr>';
				echo<<<END
					$to_co_wydrukowano_teraz
END;
				//wyślij i wyświetl dane rekord po rekordzie
				$do_pdf3 .= $to_co_wydrukowano_teraz; //składaj, rekord po rekordzie, w trzecią część danych
			}
			$do_pdf4 = '</tr></table>'; //czwarta i ostatnia część danych to zamknięcie tabeli
		}
		else if ($zdarzenie == 'alarm_pierwszego_stopnia') //jeżeli żądanym zdarzeniem jest alarm pierwszego stopnia, to...
		{
			$zapytanie_o_dane = mysqli_query($connection, $tresc_zapytania);
			if (mysqli_num_rows($zapytanie_o_dane) >= 1)
			{
				//jeśli w bazie znajdują się żądane informacje, wyślij i wyświetl drugą część danych - nazwy kolumn
				$do_pdf2 .= '<td style="position:sticky;top:73px;" width="7.5%" align="center" bgcolor="e5e5e5">L.p.</td>
							<td style="position:sticky;top:73px;" width="9.5%" align="center" bgcolor="e5e5e5">Data i godzina wystąpienia alarmu</td>
							<td style="position:sticky;top:73px;" width="9.5%" align="center" bgcolor="e5e5e5">Czujnik płomienia</td>
							<td style="position:sticky;top:73px;" width="9.5%" align="center" bgcolor="e5e5e5">Czujnik dymu</td>
							<td style="position:sticky;top:73px;" width="9.5%" align="center" bgcolor="e5e5e5">ROP</td>
							<td style="position:sticky;top:73px;" width="10%" align="center" bgcolor="e5e5e5">Czujnik temperatury</td>
							<td style="position:sticky;top:73px;" width="9%" align="center" bgcolor="e5e5e5">Wartość temperatury [&degC]</td>
							<td style="position:sticky;top:73px;" width="8.3%" align="center" bgcolor="e5e5e5">Temperatura graniczna [&degC]</td>
							<td style="position:sticky;top:73px;" width="9.3%" align="center" bgcolor="e5e5e5">Czas na potwierdzenie [s]</td>
							<td style="position:sticky;top:73px;" width="9.5%" align="center" bgcolor="e5e5e5">Czy wystąpił alarm drugiego stopnia</td>
							<td style="position:sticky;top:73px;" width="9.5%" align="center" bgcolor="e5e5e5">Czy potwierdzono alarm</td>
							<td style="position:sticky;top:73px;" width="9.5%" align="center" bgcolor="e5e5e5">Czy zresetowano system</td>
							</tr><tr>';
			
				echo<<<END
					$do_pdf2
END;
			}
			
			$do_pdf3 = '';
			
			for ($i=mysqli_num_rows($zapytanie_o_dane); $i>=1; $i--) //a następnie stwórz trzecią część danych - poszczególne rekordy spełniające żądane wytyczne
			{
				$wynik = mysqli_fetch_assoc($zapytanie_o_dane);
				//pobierz ostatni rekord ze zdarzeniami z bazy
				$aktualne_id = $wynik['id'];
				$data_godzina = $wynik['datagodzina'];
				//informacje o czujnikach i pozostałych stanach zapisz w postaci tablic
				$czujki = array($wynik['czujnik_plomienia'],$wynik['czujnik_dymu'],$wynik['rop'],$wynik['czujnik_temperatury']);
				$pozostale_stany = array($wynik['potwierdz'],$wynik['reset'],$wynik['pozar1'],$wynik['pozar2']);
				
				//jeśli dana czujka wskazała alarm, to wyświetl datę wstawienia tej informacji do bazy - jest to czas, kiedy dana czujka wskazała alarm pierwszego stopnia
				if ($czujki[0]=='1')
				{
					$czujnik_plomienia = $wynik['datagodzina'];
				}
				else if ($czujki[0]=='0')
				{
					$czujnik_plomienia = '-';
				}
				else if ($czujki[0]=='-')
				{
					$czujnik_plomienia = 'Uszkodzony';
				}
			
				if ($czujki[1]=='1')
				{
					$czujnik_dymu = $wynik['datagodzina'];
				}
				else if ($czujki[1]=='0')
				{
					$czujnik_dymu = '-';
				}
				else if ($czujki[1]=='-')
				{
					$czujnik_dymu = 'Uszkodzony';
				}
				
				if ($czujki[2]=='1')
				{
					$rop = $wynik['datagodzina'];
				}
				else if ($czujki[2]=='0')
				{
					$rop = '-';
				}
				else if ($czujki[2]=='-')
				{
					$rop = 'Uszkodzony';
				}
				//pokazuj również zmierzoną w trakcie wykrywania pożaru temperaturę (nieważne, czy czujnik temperatury wskazał w danej chwili alarm, czy nie - pokaż tą wartość)
				if ($czujki[3]=='1')
				{
					$czujnik_temperatury = $wynik['datagodzina'];
					$wartosc_temperatury = $wynik['wartosc_temperatury'];
				}
				else if ($czujki[3]=='0')
				{
					$czujnik_temperatury = '-';
					$wartosc_temperatury = $wynik['wartosc_temperatury'];
				}
				else if ($czujki[3]=='-')
				{
					$czujnik_temperatury = 'Uszkodzony';
					$wartosc_temperatury = '-';
				}
				
				//wyświetl informacje o ustawionych parametrach
				$temperatura_graniczna = $wynik['temperatura_krytyczna'];
				$czas_na_potwierdzenie = $wynik['czas_do_pozar2'];
				
				//początkowe informacje o potwierdzeniu danego zdarzenia, resecie systemu podczas danego zdarzenia czy przejścia danego zdarzenia w alarm drugiego stopnia (zdarzenie to alarm pierwszego stopnia)
				$potwierdz = '-';
				$reset = '-';
				$pozar2 = '-';
				
				for ($j=1; $j<=6; $j++) //przeszukaj tabelę ze zdarzeniami od wykrytego zdarzenia, do maksymalnie szóstego rekordu następującego po tym zdarzeniu - od momentu wykrycia alarmu, siódmy rekord na 100% nie dotyczy już bieżącego zdarzenia, gdzie jedno zdarzenie trwa od momentu jego wykrycia do momentu zaprzestania jego zgłaszania (czyli do wstawienia do bazy rekordu z wyzerowanymi stanami i czujnikami, które nie wskazują alarmu lub są uszkodzone)
				{
					//szukaj czujników, które wskazały alarm pierwszego stopnia, podczas TEGO SAMEGO ZDARZENIA
					$szukaj_czujek_do_aktualnego_zdarzenia = mysqli_query($connection, "SELECT * FROM stany_wyprowadzen WHERE id='$aktualne_id'+'$j'");
					$rezultat = mysqli_fetch_assoc($szukaj_czujek_do_aktualnego_zdarzenia);
					//zapisanie danych z kolejnych rekordów
					$nowe_czujki = array($rezultat['czujnik_plomienia'],$rezultat['czujnik_dymu'],$rezultat['rop'],$rezultat['czujnik_temperatury']);
					$nowe_pozostale_stany = array($rezultat['potwierdz'],$rezultat['reset'],$rezultat['pozar1'],$rezultat['pozar2']);
					
					//jeśli któraś z czujek (następna) wykryła alarm i pozostałe stany są bez zmian i nie są wyzerowane (np. po utracie połączenia z serwerem), to...
					if ($nowe_czujki!=$czujki && $nowe_pozostale_stany==$pozostale_stany && $nowe_pozostale_stany!=array(0,0,0,0))
					{
						//jeżeli w następnym rekordzie stan którejś z czujek się zmienił, to znaczy, że wykryła alarm i należy pokazać datę kiedy to nastąpiło, ponadto zmień stan z poprzedniego rekordu danej czujki, by nie dublować czasu wykrycia alarmu (co spowoduje błędne wyświetlanie danych)
						if ($nowe_czujki[0]=='1' && $czujki[0]!='1')
						{
							$czujnik_plomienia = $rezultat['datagodzina'];
							$czujki[0] = '1';
						}
						
						if ($nowe_czujki[1]=='1' && $czujki[1]!='1')
						{
							$czujnik_dymu = $rezultat['datagodzina'];
							$czujki[1] = '1';
						}
						
						if ($nowe_czujki[2]=='1' && $czujki[2]!='1')
						{
							$rop = $rezultat['datagodzina'];
							$czujki[2] = '1';
						}
						//pokazuj również zmierzoną w trakcie wykrywania pożaru temperaturę (nieważne, czy czujnik temperatury wskazał w danej chwili alarm, czy nie - pokaż tą wartość)
						if ($nowe_czujki[3]=='1' && $czujki[3]!='1')
						{
							$czujnik_temperatury = $rezultat['datagodzina'];
							$wartosc_temperatury = $rezultat['wartosc_temperatury'];
							$czujki[3] = '1';
						}
						else if ($nowe_czujki[3]=='0')
						{
							$wartosc_temperatury = $rezultat['wartosc_temperatury'];
						}
					}
					//jeśli kolejna czujka nie wykryła alarmu, a wykryto zmianę stanu (np. potwierdzono alarm) i stany te nie są wyzerowane (np. po utracie połączenia z serwerem), to...
					else if ($nowe_czujki==$czujki && $nowe_pozostale_stany!=$pozostale_stany  && $nowe_pozostale_stany!=array(0,0,0,0))
					{
						//w tym wypadku należy poinformować o fakcie zmiany stanu - czyli system wszedł albo w alarm drugiego stopnia, został potwierdzony alarm lub zresetowano system (wyświetlenie odpowiedniej daty)
						//ponadto zmień stan z poprzedniego rekordu danego stanu, by nie dublować czasu przejścia w nowy stan (co spowoduje błędne wyświetlanie danych)
						if ($nowe_pozostale_stany[0]=='1' && $pozostale_stany[0]!='1')
						{
							$potwierdz = $rezultat['datagodzina'];
							$pozostale_stany[0] = '1';
						}
						else if ($nowe_pozostale_stany[0]=='0' && $pozostale_stany[0]!='1')
						{
							$potwierdz = '-';
						}
						
						if ($nowe_pozostale_stany[1]=='1')
						{
							$reset = $rezultat['datagodzina'];
							$pozostale_stany[1] = '1';
						}
						else if ($nowe_pozostale_stany[1]=='0' && $pozostale_stany[1]!='1')
						{
							$reset = '-';
						}
						
						if ($nowe_pozostale_stany[3]=='1')
						{
							$pozar2 = $rezultat['datagodzina'];
							$pozostale_stany[3] = '1';
						}
						else if ($nowe_pozostale_stany[3]=='0' && $pozostale_stany[3]!='1')
						{
							$pozar2 = '-';
						}
					} //jeżeli następny rekord jest "wyzerowany", to znaczy, że aktualnie badane zdarzenie dobiegło końca i można przejść do następnego
					else if ($nowe_pozostale_stany==array(0,0,0,0))
					{
						$aktualne_id = $rezultat['id'];
						break;
					}
				}
				//wyślij i wyświetl dane rekord po rekordzie
				$to_co_wydrukowano_teraz = '<td width="7.5%" align="center">'.$i.'</td>
											<td width="9.5%" align="center">'.$data_godzina.'</td>
											<td width="9.5%" align="center">'.$czujnik_plomienia.'</td>
											<td width="9.5%" align="center">'.$czujnik_dymu.'</td>
											<td width="9.5%" align="center">'.$rop.'</td>
											<td width="10%" align="center">'.$czujnik_temperatury.'</td>
											<td width="9%" align="center">'.$wartosc_temperatury.'</td>
											<td width="8.3%" align="center">'.$temperatura_graniczna.'</td>
											<td width="9.3%" align="center">'.$czas_na_potwierdzenie.'</td>
											<td width="9.5%" align="center">'.$pozar2.'</td>
											<td width="9.5%" align="center">'.$potwierdz.'</td>
											<td width="9.5%" align="center">'.$reset.'</td>
											</tr><tr>';
				echo<<<END
					$to_co_wydrukowano_teraz
END;
				$do_pdf3 .= $to_co_wydrukowano_teraz; //składaj, rekord po rekordzie, w trzecią część danych
			}
			$do_pdf4 = '</tr></table>'; //czwarta i ostatnia część danych to zamknięcie tabeli
		}
		else if ($zdarzenie == 'alarm_drugiego_stopnia') //jeżeli żądanym zdarzeniem jest alarm drugiego stopnia, to...
		{
			if (mysqli_num_rows($zapytanie_o_dane) >= 1) 
			{
				//jeśli w bazie znajdują się żądane informacje, wyślij i wyświetl drugą część danych - nazwy kolumn
				$do_pdf2 .= '<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">L.p.</td>
							 <td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Data i godzina wystąpienia alarmu</td>
							 <td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Czujniki zgłaszające alarm</td>
					</tr><tr>';
			
				echo<<<END
					$do_pdf2
END;
			}
			
			$do_pdf3 = '';
			for ($i=mysqli_num_rows($zapytanie_o_dane); $i>=1; $i--) //a następnie stwórz trzecią część danych - poszczególne rekordy spełniające żądane wytyczne
			{
				$wynik = mysqli_fetch_assoc($zapytanie_o_dane);
				$aktualne_id = $wynik['id'];
				$data = $wynik['datagodzina'];
				$plomien = $wynik['czujnik_plomienia'];
				$dym = $wynik['czujnik_dymu'];
				$rop = $wynik['rop'];
				$temperatura = $wynik['czujnik_temperatury'];
				$czujki_wskazujace_alarm = '';
				
				if ($plomien == '1')
				{
					$czujki_wskazujace_alarm .= 'czujnik płomienia, ';
				}
				
				if ($dym == '1')
				{
					$czujki_wskazujace_alarm .= 'czujnik dymu, ';
				}
				
				if ($rop == '1')
				{
					$czujki_wskazujace_alarm .= 'rop, ';
				}
				
				if ($temperatura == '1')
				{
					$czujki_wskazujace_alarm .= 'czujnik temperatury, ';
				}
				
				$to_co_wydrukowano_teraz = '<td width="60" align="center">'.$i.'</td>
											<td width="60" align="center">'.$data.'</td>
											<td width="60" align="center">'.$czujki_wskazujace_alarm.'</td>
											</tr><tr>';
				echo<<<END
					$to_co_wydrukowano_teraz
END;
				//wyślij i wyświetl dane rekord po rekordzie
				$do_pdf3 .= $to_co_wydrukowano_teraz; //składaj, rekord po rekordzie, w trzecią część danych
			}
			$do_pdf4 = '</tr></table>'; //czwarta i ostatnia część danych to zamknięcie tabeli
		}
		
		$_SESSION['zdarzenia'] = $do_pdf1.$do_pdf2.$do_pdf3.$do_pdf4; //złącz wszystkie dane w jedną całość - dzięki temu, będzie można wygenerować plik PDF
	
		echo $do_pdf4;	//wysłanie i wyświetlenie ostatniej części danych - koniec tabeli
?>