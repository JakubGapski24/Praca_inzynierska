<?php
	//jeżeli użytkownik nie jest zalogowany, to przekieruj do panelu logowania
	session_start();
	
	if(!isset($_SESSION['loggedin']))
	{
		header('Location: index.php');
		exit();
	}

?>

<!DOCTYPE HTML> <!-- stworzenie standardowego dokumentu HTML -->
<html lang="pl">
	<head>
		<meta charset="utf-8">
		<title>Zmiana hasła</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"/>
		<link rel="stylesheet" href="ADMIN/styl_admin.css" type="text/css"/> <!-- podpięcie zewnętrznego arkusza stylów CSS -->
		<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet"> <!-- ustalenie stylu czcionki -->
	</head>
	<body>
	<div class="container">
			<!--wyświetlenie informacji o zalogowanym użytkowniku-->
			<div class="header"> Zalogowany użytkownik: <span style="color:#F41127"><?php $login = $_SESSION['nick'];$typ=$_SESSION['typ_usera'];echo $login.' ('.$typ.')';?></span></div>
			<div class="menu"> <!--skrócona wersja menu-->
				<ol>
					<li> <a href="logout.php"> Wyloguj się </a></li>
				</ol>
			</div>
			<div class="content">
				<h3 style="color:#000000"> Podaj dane potrzebne do zmiany hasła</h3>
				<form method="post"> <!-- formularz zmieniający hasło użytkownika -->
					<input type="password" id="input1" name="password" size="20" placeholder="Stare hasło" onfocus="this.placeholder='Stare hasło'" onblur="this.placeholder='Stare hasło'"><br> <!-- pole na stare, poprzednie hasło -->
					<input type="password" id="input2" name="password2" size="20" placeholder="Nowe hasło" onfocus="this.placeholder='Nowe hasło'" onblur="this.placeholder='Nowe hasło'"><br> <!-- pole na nowe hasło -->
					<input type="password" id="input3" name="password3" size="20" placeholder="Potwierdź hasło" onfocus="this.placeholder='Potwierdź hasło'" onblur="this.placeholder='Potwierdź hasło'"><br> <!-- pole na potwierdzenie nowego hasła -->
					
					<div style="padding-left:200px; padding-top:10px;font-size:16px;"><input type="checkbox" id="checkbox" class="checkbox" onclick="show()"><label for="checkbox" style="font-size:16px;">Pokaż hasło</label></div>
					<input type="submit" name="button" value="Zmiana hasła"/> <!-- przycisk zatwierdzający zmianę hasła (formularz) -->
				</form>
				
				<script>
						function show() { //dzięki tej funkcji, można zobaczyć wpisywane hasło
								//pobranie wpisywanych danych
								var x = document.getElementById("input1");
								var y = document.getElementById("input2");
								var z = document.getElementById("input3");
								if (x.type === "password" && y.type === "password" && z.type === "password") { //jeśli hasło jest ukryte (kropki)
									x.type = "text"; //to zamień je na tekst
									y.type = "text";
									z.type = "text";
								} 
								else 
								{ //w przeciwnym razie, ukryj je z powrotem
									x.type = "password";
									y.type = "password";
									z.type = "password"
								}
							}
					</script>
<?php
				//pobierz dane bazy, z którą chcesz się połączyć
				require_once "connect.php";
				$connection = mysqli_connect($host, $user, $password);
				mysqli_query($connection, "SET CHARSET utf8");
				mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
				mysqli_select_db($connection, $db);	
				
				//sprawdź, czy zatwierdzono zmianę hasła oraz czy wszystkie pola są zapełnione
				if (isset($_POST['button']) && !empty($_POST['password']) && !empty($_POST['password2']) && !empty($_POST['password3'])) 
				{
					//zapisz pobrane dane
					$stare_haslo = $_POST['password'];
					$zahaszowane_stare_haslo = hash('sha3-512', $stare_haslo); //zaszyfruj stare hasło (potrzebne do porównania z hasłem w bazie)
					$nowe_haslo = $_POST['password2'];
					$potwierdz_haslo = $_POST['password3'];
					
					$zapytanie = mysqli_query($connection, "SELECT haslo FROM users WHERE login = '$login'");
					$wynik = mysqli_fetch_assoc($zapytanie);
					
					if ($wynik['haslo'] == $zahaszowane_stare_haslo) //sprawdź, czy wpisane stare hasło jest zgodne z hasłem w bazie (dla zalogowanego usera)
					{
						if (strlen($nowe_haslo) < 8) //sprawdź, czy nowe hasło ma minimum 8 znaków
						{	
							echo("<br>");
							echo '<span style="color:red">Minimalna długość hasła to 8 znaków!</span>';
						}
						//sprawdź, czy nowe hasło ma co najmniej jedną dużą i małą literę, jedną cyfrę oraz jeden znak specjalny
						else if ((!preg_match('/[a-z]/', $nowe_haslo)) || (!preg_match('/[A-Z]/', $nowe_haslo)) || (!preg_match('/[0-9]/', $nowe_haslo)) || (!preg_match('/[\(\)\{\}\!\@\#\$\%\^\&\*\-\_\=\+\>\<\?\,\.]/', $nowe_haslo)))
						{
							echo("<br>");
							echo '<span style="color:red">Brak cyfry, małej litery, dużej litery i/lub znaku specjalnego w haśle!</span>';
						}
						else if ($nowe_haslo != $potwierdz_haslo) //sprawdź, czy wpisane nowe hasło i jego potwierdzenie są takie same
						{
							echo("<br>");
							echo '<span style="color:red">Nowe hasło i potwierdzenie hasła są różne!';
						}
						else //jeśli wszystkie testy, przebiegły pomyślnie, to...
						{
							//zaszyfruj nowe hasło i wstaw je do bazy danych
							$zahaszowane_nowe_haslo = hash('sha3-512', $nowe_haslo);
							$update = mysqli_query($connection, "UPDATE users SET haslo='$zahaszowane_nowe_haslo', czy_losowe=0 WHERE login='$login'");
							if ($update)
							{
								//jeżeli wstawienie do danych bazy przebiegło pomyślnie, to wyświetl odpowiedni komunikat
								echo("<br>");
								echo '<span style="color:black">Twoje nowe hasło to: </span>'.$nowe_haslo;
								echo("<br>");
								echo '<span style="color:black">Wyloguj się i zaloguj się ponownie!';
							}
							else //w przeciwnym razie poinformuj o błędzie połączenia z bazą
							{
								echo("<br>");
								echo '<span style="color:red">Błąd połączenia z bazą, spróbuj jeszcze raz</span>';
							}
						}
					}
					else //w przypadku błędnego starego hasła pokaż odpowiedni komunikat
					{
						echo("<br>");
						echo '<span style="color:red">Stare hasło jest niepoprawne!</span>';
					}
				}	
				else if (isset($_POST['button']))
				{
					//jeżeli któreś z pól jest puste, to wyświetl odpowiedni komunikat
					if (empty($_POST['password']) || empty($_POST['password2']) || empty($_POST['password3']))
					{
						echo("<br>");
						echo '<span style="color:red">Pole/pola z hasłem są puste!</span>';
					}
				}
?>
</div>
	</div>
	</body>
</html>