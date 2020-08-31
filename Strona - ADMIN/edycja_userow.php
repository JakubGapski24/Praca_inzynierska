<?php
	//jeżeli użytkownik nie jest zalogowany, to przekieruj do panelu logowania
	session_start();
	
	if(!isset($_SESSION['loggedin']))
	{
		header('Location: \index.php');
		exit();
	}

?>

<!DOCTYPE HTML> <!-- stworzenie standardowego dokumentu HTML -->
<html lang="pl">
	<head>
		<meta charset="utf-8">
		<title>Edycja kont użytkowników</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"/>
		<link rel="stylesheet" href="styl_admin.css" type="text/css"/> <!-- podpięcie zewnętrznego arkusza stylów CSS -->
		<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet"> <!-- ustalenie stylu czcionki -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> <!-- podpięcie stylu, dzięki któremu możliwe będzie stworzenie responsywnego menu -->
	</head>
	<body>
	<div class="container">
			<!--wyświetlenie informacji o zalogowanym użytkowniku-->
			<div class="header"> Zalogowany użytkownik: <span style="color:#F41127"><?php $login = $_SESSION['nick'];$typ=$_SESSION['typ_usera'];echo $login.' ('.$typ.')';?></span></div>
			<div class="menu" id="MENU"> <!-- pełna wersja menu dla admina -->
				<ol>
					<li> <a href="portal_admin.php"> Strona główna </a></li>
					<li class="active"> <a href="#"> Zarządzanie użytkownikami &#9660 </a>
						<ul>
							<li><a href="rejestracja_nowego_usera.php"> Dodanie nowego użytkownika </a></li>
							<li class="active"><a href="edycja_userow.php"> Edycja kont użytkowników </a> </li>
							<li><a href="usuwanie_userow.php"> Usuwanie kont użytkowników </a> </li>
						</ul>
					</li>
					<li> <a href="logowania_uzytkownikow.php"> Logowania użytkowników </a></li>
					<li> <a href="rejestr_zdarzen.php"> Rejestr zdarzeń </a></li>
					<li> <a href="ustaw_plan.php"> Ustaw plan budynku </a></li>
					<li> <a href="ustawienia_systemu.php"> Ustawienia systemu </a></li>
					<li> <a href="informacje.php"> Informacje o systemie </a></li>
					<li> <a href="logout.php"> Wyloguj się </a></li>
					<li class="icon"> <a href="javascript:void(0);" onclick="myFunction()">
							<i class="fa fa-bars"></i>
						 </a> </li>
				</ol>
			</div>
			
			<script>
				function myFunction() { //funkcja, dzięki której możliwa jest obsługa responsywnego menu - utworzenie nowej nazwy, dzięki czemu w pliku CSS będzie wykonywany odpowiedni kod
					var x = document.getElementById("MENU");
					if (x.className === "menu") {
						x.className += " responsive";
					} else {
						x.className = "menu";
					}
				}
			</script>
			
<!-- **************************************************** wyświetl listę użytkowników ************************************************************ -->

			<div id="tabela" style="font-size: 20px; padding-top: 20px;">
				
				<h2 style="color:#000000"> Lista użytkowników </h2>
					<table width="95%" align="center" border="1" bordercolor="#303030"  cellpadding="0" cellspacing="0"> 
					<tr>
				<?php
					//pobierz dane bazy, z którą chcesz się połączyć
					require_once "connect.php";
					$connection = mysqli_connect($host, $user, $password);
					mysqli_query($connection, "SET CHARSET utf8");
					mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
					mysqli_select_db($connection, $db);
					
					$_SESSION['CZY_USUNIETO'] = false; //dzięki temu parametrowi, możliwe jest prawidłowe wyświetlanie komunikatu o usunięciu danego użytkownika w podstronie "usuwanie userów"
					$zapytanie1 = mysqli_query($connection,"SELECT * FROM users");
					
					if (mysqli_num_rows($zapytanie1) >= 1) //wyświetl nazwy kolumn tabeli
					{
						echo<<<END
						<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Login</td>
						<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Adres e-mail</td>
						<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Typ użytkownika</td>
						<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Ostatnia modyfikacja</td>
						<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Administrator dokonujący modyfikacji</td>
						<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Wybór konta do edycji</td>
						</tr><tr>
END;
					}
					
					for ($i=1; $i<=mysqli_num_rows($zapytanie1); $i++) //dopóki są rekordy w tej tabeli, to wyświetlaj dane (czyli pokaż wszystkich użytkowników)
					{
						$wynik1 = mysqli_fetch_assoc($zapytanie1);
						$login_z_bazy = $wynik1['login'];
						$email = $wynik1['email'];
						$typ = $wynik1['typ'];
						$data = $wynik1['ostatnia_modyfikacja'];
						$kto = $wynik1['kto'];
							//ostatnia kolumna to przyciski wyboru, z których każdy jest unikatowy
								echo<<<END
								<td width="150" align="center">$login_z_bazy</td>
								<td width="150" align="center">$email</td>
								<td width="150" align="center">$typ</td>
								<td width="150" align="center">$data</td>
								<td width="150" align="center">$kto</td>
								<td width="150" align="center"><input type="radio" id="radio$i" class="radio" name="edycja" value="$login_z_bazy" onclick="wybierz_usera()"><label for="radio$i" style="height:16px;"></label></td>
								</tr><tr>
END;
					}	
				?>
		</tr></table>
				
					<script>
						function wybierz_usera() { //funkcja umożliwiająca edycję wybranego konta
								var konto = document.querySelector('input[name="edycja"]:checked').value; //pobierz, odczytaj wartość klikniętego przycisku typu 'radio' - czyli przypisz login wybranego użytkownika
								document.getElementById("konto").value = konto; //wyświetl ten login przy napisie "Wybrany użytkownik"
								document.getElementById("konto1").value = konto; //przypisz ten sam login do zmiennej, niewidocznej z poziomu strony, a która umożliwi zmianę danych wybranego użytkownika
								window.scrollTo(0, document.documentElement.scrollHeight); //przesuń stronę na sam dół
						}
					</script>
					
					<form method="post"> <!-- formularz edytujący konto użytkownika -->
					<br>
					<h2> Wybrany użytkownik: <output style="color:#0A1EA7;" id="konto"></output> </h2> <!-- wyświetl wybrany login -->
					<input type="hidden" name="wybrany_user" id="konto1"> <!-- przypisz ten login do niewidocznej na ekranie zmiennej -->
					<!-- wybierz typ użytkownika -->
					<select name="user">
							<option disabled="" selected>Typ użytkownika (opcjonalnie)</option>
							<option>Administrator</option>
							<option>Operator</option>
					</select> <br>
					<!-- pole loginu -->
					<input type="text" name="Login" size="20" placeholder="Nowy login (opcjonalnie)" onfocus="this.placeholder='Nowy login (opcjonalnie)'" onblur="this.placeholder='Nowy login (opcjonalnie)'"><br>
					<!-- wybór, czy chcesz resetować hasło (sprowadzić je do postaci losowej), czy nie -->
					<input type="checkbox" id="checkbox" class="checkbox" name="haslo" value=1><label for="checkbox" style="font-size:16px;">Zresetuj hasło (opcjonalnie)</label> <br>
					<!-- pole na adres e-mail -->
					<input type="text" name="email" size="20" placeholder="Nowy adres e-mail (opcjonalnie)" onfocus="this.placeholder='Nowy adres e-mail (opcjonalnie)'" onblur="this.placeholder='Nowy adres e-mail (opcjonalnie)'"><br>
					<!-- przycisk zatwierdzający zmiany -->
					<input type="submit" name="button" value="Zapisz zmiany"/>
				</form>
				<div class="content">
				<?php 
					
					if ($_POST['button'] && $_POST['wybrany_user']!='') //jeśli zatwierdzono formularz edytujący konto oraz faktycznie wybrano któreś z istniejących kont, to...
					{
						//zapisz pobrane dane
						$wybrany_user = $_POST['wybrany_user'];
						$typ_usera_input = $_POST['user'];
						$login_input = $_POST['Login'];
						$haslo_checkbox = $_POST['haslo'];
						$email_input = $_POST['email'];
						
						//pobierz aktualny login i adres e-mail
						$zapytanie_o_dane = mysqli_query($connection, "SELECT * FROM users WHERE login='$wybrany_user'");
						$i = mysqli_fetch_assoc($zapytanie_o_dane);
								
						$aktualny_login = $i['login'];
						$aktualny_email = $i['email'];
						
						$zapytanie = mysqli_query($connection, "SELECT login FROM users WHERE login = '$login_input'");
						$ile = mysqli_num_rows($zapytanie);
						$szablon = '/^[a-zA-Z0-9\.\-_]+\@[a-zA-Z0-9\.\-_]+\.[a-z]{2,6}$/D'; //wyrażenie regularne, na podstawie którego testowany jest e-mail
						
						if ($typ_usera_input=='' && $login_input=='' && $haslo_checkbox==0 && $email_input=='') //jeżeli wszystkie pola formularza są puste, to wyświetl odpowiedni komunikat
						{
							echo("<br>");
							echo '<span style="color:black">Nie zostały wprowadzone żadne zmiany dla wybranego konta</span>';
							echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
						}
						else //jeżeli chociaż jedno pole nie jest puste, to...
						{
							if ($email_input!='' && (!preg_match($szablon,$email_input))) //jeśli pole z adresem e-mail nie jest puste, to sprawdź jego poprawność
							{
								echo("<br>");
								echo '<span style="color:red">Podany adres e-mail jest niepoprawny! Spróbuj jeszcze raz!</span>';
								echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
							}
							else if ($login_input!='' && $ile >= 1) //jeżeli pole z loginem nie jest puste, to sprawdź, czy nie jest już zajęty
							{
								echo("<br>");
								echo '<span style="color:red">Podany login jest już zajęty! Spróbuj jeszcze raz!</span>';
								echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
							}
							else  //jeśli podany login nie istnieje, to...
							{
								$zmieniono_haslo = false; //ustaw zmienną pomocniczą informującą czy zmieniono hasło, czy nie
								$zmieniono_typ_usera = false;
								$zmieniono_login = false;
								$zmieniono_maila = false;
								
								if ($typ_usera_input!='') //jeśli wybrano typ użytkownika
								{
									//to ustaw odpowiednią nazwę wybranego typu
									if ($typ_usera_input == 'Administrator')
									{
										$typ_usera_input = 'ADMINISTRATOR';
									}
									else if ($typ_usera_input == 'Operator') 
									{
										$typ_usera_input = 'OPERATOR';
									
									}
									//i zaktualizuj tą informację
									mysqli_query($connection, "UPDATE users SET typ='$typ_usera_input', ostatnia_modyfikacja=NOW(), kto='$login' WHERE login='$wybrany_user'");
									echo("<br>");
									echo '<span style="color:black">Zmieniono typ użytkownika na </span>'.$typ_usera_input;
									echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
									$zmieniono_typ_usera = true;
								}
								
								if ($haslo_checkbox==1) //jeśli zresetowano hasło, to...
								{
									$losowy_ciag = substr(md5(uniqid(rand())), 0, 6); //losowanie 6 dowolnych znaków - spośród małych liter i cyfr
							
									$wielkie_litery = str_split('ABCDEFGHIJKLMNOPRSTUWXYZ'); //z typu String powstaje tablica z tymi literami
									$znaki_specjalne = str_split('(){}!@#$%^&*-_=+/><?,.'); //z typu String powstaje tablica z tymi znakami specjalnymi
							
									$wybrana_wielka_litera = $wielkie_litery[rand(1,24)-1]; //losowanie wielkiej litery
									$wybrany_znak_specjalny = $znaki_specjalne[rand(1,22)-1]; //losowanie znaku specjalnego
							
									$haslo = str_shuffle($losowy_ciag.$wybrana_wielka_litera.$wybrany_znak_specjalny); //dodanie do 6-znakowego hasła wielkiej litery i znaku specjalnego, po czym znaki w tym haśle są losowo przestawiane (powstaje losowe, 8-znakowe hasło)
							
									$zahaszowane_haslo = hash('sha3-512', $haslo); //zaszyfrowanie hasła losowego i zaktualizowanie w odpowiednim miejscu w bazie
								
									mysqli_query($connection, "UPDATE users SET haslo='$zahaszowane_haslo', czy_losowe=1, ostatnia_modyfikacja=NOW(), kto='$login' WHERE login='$wybrany_user'");
									echo("<br>");
									echo '<span style="color:black"> Wygenerowano nowe hasło </span>';
									echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
									$zmieniono_haslo = true;
								}
							
								if ($email_input!='' && preg_match($szablon,$email_input)) //jeszcze raz upewnij się, że pole z adresem e-mail nie jest puste i adres ten jest poprawny
								{
									//zaktualizauj adres e-mail
									mysqli_query($connection, "UPDATE users SET email='$email_input', ostatnia_modyfikacja=NOW(), kto='$login' WHERE login='$wybrany_user'");
									echo("<br>");
									echo '<span style="color:black">Zmieniono adres e-mail na </span>'.$email_input;
									echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
									$zmieniono_maila = true;
								}
								
								if ($login_input!='') //upewnij się, że pole z loginem nie jest puste
								{
									//i zaktualizauj ten login
									mysqli_query($connection, "UPDATE users SET login='$login_input', ostatnia_modyfikacja=NOW(), kto='$login' WHERE login='$wybrany_user'");
									echo("<br>");
									echo '<span style="color:black">Zmieniono login użytkownika na </span>'.$login_input;
									echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
									$zmieniono_login = true;
								}
								
								//wysłanie danych na przypisany do konta adres e-mail
								echo("<br>");
								echo '<span style="color:black">Zaktualizowane dane logowania zostały wysłane na przypisany do wybranego użytkownika adres mailowy.</span>';
								echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
								
								$tytul = "Uaktualnione dane do konta - portal SAP";
								
								if ($zmieniono_login)
								{
									$aktualny_login = $login_input;
								}
								
								if ($zmieniono_maila) 
								{
									$aktualny_email = $email_input;
								}
								
								$wiadomosc = 'Administrator uaktualnił dane logowania do Twojego konta. ';
								
								if ($zmieniono_typ_usera)
								{
									$wiadomosc .= 'Zmieniono Twoje uprawnienia na: '.$typ_usera_input.'. ';
								}
								else if (!$zmieniono_typ_usera)
								{
									$wiadomosc .= 'Nie zmieniono Twoich uprawnień. ';
								}
								
								if ($zmieniono_haslo) //w zależności, czy zmieniono hasło czy nie, wyślij maila z odpowiednią treścią
								{
									$wiadomosc .= 'Twoje dane logowania:   login:'.$aktualny_login.'   hasło:'.$haslo;
								}
								else if (!$zmieniono_haslo)
								{
									$wiadomosc .= 'Twoje dane logowania:   login:'.$aktualny_login.'   hasło pozostało bez zmian';
								}
								
								mail($aktualny_email, $tytul, $wiadomosc);
							}
						}
					}
					else if (isset($_POST['button']) && $_POST['wybrany_user']=='') //jeżeli zatwierdzono edycję konta, ale żadnego nie wybrano, to wyświetl odpowiedni komunikat
					{
						echo("<br>");
						echo '<span style="color:red">Nie zostało wybrane żadne konto!</span>';
						echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
					}
				?>
				</div>
</div>
	</div>
	</body>
</html>