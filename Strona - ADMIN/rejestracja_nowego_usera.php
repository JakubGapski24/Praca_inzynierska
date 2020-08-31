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
		<title>Dodaj nowego usera</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"/>
		<link rel="stylesheet" href="styl_admin.css" type="text/css"/> <!-- podpięcie zewnętrznego arkusza stylów CSS -->
		<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet"> <!-- ustalenie stylu czcionki -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> <!-- podpięcie stylu, dzięki któremu możliwe będzie stworzenie responsywnego menu -->
	</head>
	<body>
	<div class="container">
			<!--wyświetlenie informacji o zalogowanym użytkowniku-->
			<div class="header"> Zalogowany użytkownik: <span style="color:#F41127"><?php $login1 = $_SESSION['nick'];$typ=$_SESSION['typ_usera'];echo $login1.' ('.$typ.')';?></span></div>
			<div class="menu" id="MENU"> <!-- pełna wersja menu dla admina -->
				<ol>
					<li> <a href="portal_admin.php"> Strona główna </a></li>
					<li class="active"> <a href="#"> Zarządzanie użytkownikami &#9660 </a>
						<ul>
							<li class="active"><a href="rejestracja_nowego_usera.php"> Dodanie nowego użytkownika </a></li>
							<li><a href="edycja_userow.php"> Edycja kont użytkowników </a> </li>
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
			
			<div class="content">
				<h3 style="color:#000000"> Podaj dane nowego użytkownika</h3>
				<form method="post"> <!-- formularz dodający nowego użytkownika -->
					<!-- wybór typu użytkownika -->
					<select name="user"> 
							<option disabled="" selected>Typ użytkownika</option>
							<option>Administrator</option>
							<option>Operator</option>
					</select> <br>
					<input type="text" name="login" size="20" placeholder="Login" onfocus="this.placeholder='Login'" onblur="this.placeholder='Login'"><br> <!-- pole na login -->
					<input type="text" name="e-mail" size="20" placeholder="Adres e-mail" onfocus="this.placeholder='Adres e-mail'" onblur="this.placeholder='Adres e-mail'"><br> <!-- pole na adres e-mail -->
					<input type="submit" style="width:500px;" name="button" value="Generuj losowe hasło i załóż nowe konto"/> <!-- przycisk zatwierdzający stworzenie nowego użytkownika -->
				</form>
				
<?php
				$_SESSION['CZY_USUNIETO'] = false; //dzięki temu parametrowi, możliwe jest prawidłowe wyświetlanie komunikatu o usunięciu danego użytkownika w podstronie "usuwanie userów"
				
				//pobierz dane bazy, z którą chcesz się połączyć
				require_once "connect.php";
				$connection = mysqli_connect($host, $user, $password);
				mysqli_query($connection, "SET CHARSET utf8");
				mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
				mysqli_select_db($connection, $db);	
				
				//sprawdź, czy zatwierdzono stworzenie nowego użytkownika oraz, czy wszystkie pola są zapełnione
				if (isset($_POST['button']) && !empty($_POST['user']) && !empty($_POST['login']) && !empty($_POST['e-mail'])) 
				{
					//zapisz pobrane dane
					$login = $_POST['login'];
					$email = $_POST['e-mail'];
					$typ_usera = $_POST['user'];
					
					$szablon = '/^[a-zA-Z0-9\.\-_]+\@[a-zA-Z0-9\.\-_]+\.[a-z]{2,6}$/D'; //wyrażenie regularne, na podstawie którego testowany jest e-mail
					
					//sprawdź, czy istnieje użytkownik o podanym loginie
					$zapytanie = mysqli_query($connection, "SELECT login FROM users WHERE login = '$login'");
					$ile = mysqli_num_rows($zapytanie);
						
					if ($ile >= 1) //jeśli istnieje już taki login (użytkownik), wyświetl odpowiedni komunikat
					{
						echo("<br>");
						echo '<span style="color:red">Podany login jest już zajęty!</span>';
					}
					else //w przeciwnym wypadku ustaw odpowiednio typ użytkownika
					{
						if ($typ_usera == 'Administrator')
						{
							$typ_usera = 'ADMINISTRATOR';
						}
						else 
						{
							$typ_usera = 'OPERATOR';
						}
					
						if(preg_match($szablon,$email)) //następnie sprawdź, czy podany adres e-mail jest prawidłowy
						{
							$losowy_ciag = substr(md5(uniqid(rand())), 0, 6); //losowanie 6 dowolnych znaków - spośród małych liter i cyfr
							
							$wielkie_litery = str_split('ABCDEFGHIJKLMNOPRSTUWXYZ'); //z typu String powstaje tablica z tymi literami
							$znaki_specjalne = str_split('(){}!@#$%^&*-_=+/><?,.'); //z typu String powstaje tablica z tymi znakami specjalnymi
							
							$wybrana_wielka_litera = $wielkie_litery[rand(1,24)-1]; //losowanie wielkiej litery
							$wybrany_znak_specjalny = $znaki_specjalne[rand(1,22)-1]; //losowanie znaku specjalnego
							
							$haslo = str_shuffle($losowy_ciag.$wybrana_wielka_litera.$wybrany_znak_specjalny); //dodanie do 6-znakowego hasła wielkiej litery i znaku specjalnego, po czym znaki w tym haśle są losowo przestawiane (powstaje losowe, 8-znakowe hasło)
							
							$zahaszowane_haslo = hash('sha3-512', $haslo); //zaszyfrowanie hasła losowego i wstawienie danych w odpowiednie miejsce do bazy danych
							
							$wstaw = mysqli_query($connection, "INSERT INTO users (login,email,haslo,typ,czy_losowe,ostatnia_modyfikacja,kto) VALUES('$login','$email','$zahaszowane_haslo','$typ_usera',1,NOW(),'$login1')");
							if ($wstaw)
							{
								//jeżeli wstawienie do danych bazy przebiegło pomyślnie, to wyświetl odpowiedni komunikat
								echo("<br>");
								echo '<span style="color:black">Rejestracja przebiegła pomyślnie</span>';
								echo("<br>");
								echo '<span style="color:black">Na podany adres e-mail wysłany został login: </span>'.$login.'<span style="color:black;"> oraz hasło przypisane do tego konta </span>';
								
								//wysłanie danych na podany adres e-mail
								$tytul = "Witamy wśród użytkowników portalu SAP!";
								$wiadomosc = 'Twoje dane do portalu: '."   ".'login: '.$login.'   hasło: '.$haslo;
								mail($email, $tytul, $wiadomosc);
							}
							else //w przeciwnym razie poinformuj o błędzie połączenia z bazą
							{
								echo("<br>");
								echo '<span style="color:red">Błąd połączenia z bazą, spróbuj jeszcze raz</span>';
							}
						}
						else //jeżeli podany adres e-mail jest nieprawidłowy, to wyświetl odpowiedni komunikat
						{
							echo("<br>");
							echo '<span style="color:red">Podany e-mail jest niepoprawny!</span>';
						}
					}
				}		
				else if (isset($_POST['button'])) //jeżeli zatwierdzono tworzenie nowego użytkownika, ale któreś z pól jest puste, to wyświetl odpowiedni komunikat
				{
					if (empty($_POST['user']))
					{
						echo("<br>");
						echo '<span style="color:red">Nie wybrano typu użytkownika!</span>';
					}
					
					if (empty($_POST['login']))
					{
						echo("<br>");
						echo '<span style="color:red">Pole z loginem jest puste!</span>';
					}
					
					if (empty($_POST['e-mail']))
					{
						echo("<br>");
						echo '<span style="color:red">Pole z e-mailem jest puste!</span>';
					}
				}
?>
</div>

<!-- **************************************************** wyświetl listę użytkowników ************************************************************ -->

<div id="tabela" style="font-size: 20px; padding-top: 20px;">
				<h2 style="color:#000000"> Lista użytkowników </h2>
					<table width="95%" align="center" border="1" bordercolor="#303030"  cellpadding="0" cellspacing="0"> 
					<tr>
					
				<?php
				
				$zapytanie1 = mysqli_query($connection,"SELECT * FROM users");
				
				if (mysqli_num_rows($zapytanie1) >= 1) //wyświetl nazwy kolumn tabeli
				{
					echo<<<END
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Login</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Adres e-mail</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Typ użytkownika</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Ostatnia modyfikacja</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Administrator dokonujący modyfikacji</td>
					</tr><tr>
END;
				}
				
				for ($i=1; $i<=mysqli_num_rows($zapytanie1); $i++) //dopóki są rekordy w tej tabeli, to wyświetlaj dane (czyli pokaż wszystkich użytkowników)
				{
					$wynik1 = mysqli_fetch_assoc($zapytanie1);
					$login = $wynik1['login'];
					$email = $wynik1['email'];
					$typ = $wynik1['typ'];
					$data = $wynik1['ostatnia_modyfikacja'];
					$kto = $wynik1['kto'];
					
						
							echo<<<END
							<td width="150" align="center">$login</td>
							<td width="150" align="center">$email</td>
							<td width="150" align="center">$typ</td>
							<td width="150" align="center">$data</td>
							<td width="150" align="center">$kto</td>
							</tr><tr>
END;
				}	
		?>
		</tr></table>
	</div>
	</body>
</html>