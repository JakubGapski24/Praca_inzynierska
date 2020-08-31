<?php
	
	session_start(); //rozpocznij sesję
	
	if ((isset($_SESSION['loggedin'])) && ($_SESSION['loggedin'])) //jeśli zalogowany jest już jakiś użytkownik 
	{
		if ($_SESSION['czy_losowe']) //sprawdź, czy jego hasło nie jest losowym ciągiem znaków
		{
			header('Location: zmiana_hasla_losowego.php'); //jeśli jest, to przekieruj go do strony zmiany tego hasła
			exit();
		}
		else if ($_SESSION['typ_usera']=='ADMINISTRATOR') //jeśli hasło nie jest losowe i logujący się użytkownik jest adminem, to przekieruj go na portal admina
		{
			header('Location: ADMIN/portal_admin.php');
			exit();
		}
		else //w innym przypadku, przekieruj logującego się użytkownika na portal usera
		{
			header('Location: USER/portal.php');
			exit();
		}
	}
?>
<!DOCTYPE HTML> <!-- stworzenie standardowego dokumentu HTML -->
<html lang="pl">
	<head>
		<meta charset="utf-8">
		<title>Zresetuj hasło</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"/>
		<link rel="stylesheet" href="style.css" type="text/css"/> <!-- podpięcie zewnętrznego arkusza stylów CSS -->
		<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet"> <!-- ustalenie stylu czcionki -->
	</head>
	<body>
	<a href="index.php"> Powrót do panelu logowania </a>
		<div id="container">
			<div id="content">
				<br /> Podaj swoje dane, które są potrzebne do zresetowania hasła
				<form method="post"> <!-- formularz resetujący hasło użytkownika -->
					<input type="text" name="login" maxlength="20" size="20" placeholder="Login" onfocus="this.placeholder='Login'" onblur="this.placeholder='Login'"><br> <!-- pole na login -->
					<input type="text" name="email" maxlength="20" size="20" placeholder="Adres e-mail" onfocus="this.placeholder='Adres e-mail'" onblur="this.placeholder='Adres e-mail'"><br> <!-- pole na adres e-mail -->
				<input type="submit" name="button" value="Zresetuj hasło"/> <!-- przycisk zatwierdzający reset hasła (formularz) -->
				</form>
				<br />
				<?php
					//pobierz dane bazy, z którą chcesz się połączyć
					require_once "connect.php";
					$connection = mysqli_connect($host, $user, $password);
					mysqli_query($connection, "SET CHARSET utf8");
					mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
					mysqli_select_db($connection, $db);
				
			//sprawdź, czy zatwierdzono zmianę hasła oraz czy wszystkie pola są zapełnione				
				if (isset($_POST['button']) && !empty($_POST['login']) && !empty($_POST['email'])) 
				{
					//zapisz pobrane dane
					$login = $_POST['login'];
					$email = $_POST['email'];
					
					$szablon = '/^[a-zA-Z0-9\.\-_]+\@[a-zA-Z0-9\.\-_]+\.[a-z]{2,6}$/D'; //wyrażenie regularne, na podstawie którego testowany jest e-mail
					
					//sprawdź, czy istnieje użytkownik o podanym loginie
					$zapytanie = mysqli_query($connection, "SELECT email FROM users WHERE login = '$login'");
					$ile = mysqli_num_rows($zapytanie);
					if ($ile < 1) //jeśli podany login nie istnieje, to pokaż odpowiedni komunikat
					{	
						echo("<br>");
						echo '<span style="color:red">Podany login i/lub e-mail jest niepoprawny!</span>';
					}
					else //jeśli isttnieje, to...
					{
						$wynik = mysqli_fetch_assoc($zapytanie);
						$email_z_bazy = $wynik['email']; //pobierz z bazy danych adres e-mail tego użytkownika
						
						if(preg_match($szablon,$email) && $email == $email_z_bazy) //sprawdź, czy wpisany adres e-mail jest prawidłowym adresem oraz czy jest on adresem tego konkretnego użytkownika
						{
							$losowy_ciag = substr(md5(uniqid(rand())), 0, 6); //losowanie 6 dowolnych znaków - spośród małych liter i cyfr
							
							$wielkie_litery = str_split('ABCDEFGHIJKLMNOPRSTUWXYZ'); //z typu String powstaje tablica z tymi literami
							$znaki_specjalne = str_split('(){}!@#$%^&*-_=+/><?,.'); //z typu String powstaje tablica z tymi znakami specjalnymi
							
							$wybrana_wielka_litera = $wielkie_litery[rand(1,24)-1]; //losowanie wielkiej litery
							$wybrany_znak_specjalny = $znaki_specjalne[rand(1,22)-1]; //losowanie znaku specjalnego
							
							$haslo = str_shuffle($losowy_ciag.$wybrana_wielka_litera.$wybrany_znak_specjalny); //dodanie do 6-znakowego hasła wielkiej litery i znaku specjalnego, po czym znaki w tym haśle są losowo przestawiane (powstaje losowe, 8-znakowe hasło)
							
							$zahaszowane_haslo = hash('sha3-512', $haslo); //zaszyfrowanie hasła losowego i wstawienie go w odpowiednie miejsce do bazy danych
							
							mysqli_query($connection, "UPDATE users SET haslo='$zahaszowane_haslo', czy_losowe=1 WHERE login='$login'");
							
							echo '<span style="color:yellow">Na podany adres e-mail wysłane zostało hasło dla użytkownika: </span>'.$login;
							
							//wysłanie tego hasła na podany adres e-mail
							$tytul = "Zresetowanie hasła do konta - portal SAP";
							$wiadomosc = 'Hasło dla użytkownika: '."   ".$login.'   hasło: '.$haslo;
							mail($email, $tytul, $wiadomosc);
						}
						else //jeżeli podany adres e-mail jest niezgodny z tym w bazie, to wyświetl odpowiedni komunikat
						{
							echo("<br>");
							echo '<span style="color:red">Podany login i/lub e-mail jest niepoprawny!</span>';
						}	
					}
				}
				else if (isset($_POST['button'])) //jeżeli zatwierdzono resetowanie hasła, ale któreś z pól jest puste, to wyświetl odpowiedni komunikat
				{
										
					if (empty($_POST['login']))
					{
						echo("<br>");
						echo '<span style="color:red">Pole z loginem jest puste!</span>';
					}
					
					if (empty($_POST['email']))
					{
						echo("<br>");
						echo '<span style="color:red">Pole z e-mailem jest puste!</span>';
					}
				}
?>
			</div>
		</div>
	</body>
</html>