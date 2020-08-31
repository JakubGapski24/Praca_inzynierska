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
		<title>Praca Inżynierska - Jakub Gapski</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"/>
		<link rel="stylesheet" href="style.css" type="text/css"/> <!-- podpięcie zewnętrznego arkusza stylów CSS -->
		<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet"> <!-- ustalenie stylu czcionki -->
	</head>
	<body>
		<div id="container">
			<div id="header"> Witaj w internetowym systemie sygnalizacji pożaru (SAP) </div>
			<div id="content">
				<br /> Podaj dane, aby się zalogować
				<form method="post"> <!-- stworzenie formularza do logowania -->
					<input type="text" name="login" maxlength="20" size="20" placeholder="Login" onfocus="this.placeholder='Login'" onblur="this.placeholder='Login'"><br> <!-- pole na login -->
					<input type="password" id="input" name="password" maxlength="20" size="20" placeholder="Hasło" onfocus="this.placeholder='Hasło'" onblur="this.placeholder='Hasło'"><br> <!-- pole na hasło -->
					<a href="zresetuj_haslo.php"> Nie pamiętam hasła</a> &nbsp &nbsp &nbsp <!-- link do podstrony resetującej hasło -->
					<input type="checkbox" id="checkbox" class="checkbox" onclick="show()"><label for="checkbox" style="font-size:16px;">Pokaż hasło</label><br>
					
					<script>
						function show() { //dzięki tej funkcji, można zobaczyć wpisywane hasło
								var x = document.getElementById("input"); //pobranie id="input" (hasła)
									if (x.type === "password") { //jeśli hasło jest ukryte (kropki)
										x.type = "text"; //to zamień je na tekst
									} 
									else { //w przeciwnym razie, ukryj je z powrotem
										x.type = "password";
									}
						}
					</script>
					
				<input type="submit" name="button" value="Zaloguj się"/> <!-- przycisk potwierdzający wykonanie formularza -->
				</form>
				<br />
				<?php
					//pobierz dane bazy, z którą chcesz się połączyć
					require_once "connect.php";
					$connection = mysqli_connect($host, $user, $password);
					mysqli_query($connection, "SET CHARSET utf8");
					mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
					mysqli_select_db($connection, $db);
					
					if (isset($_POST['button'])) //jeżeli formularz wysłany
					{
						//pobierz wpisane dane
						$login = $_POST['login'];
						$password = $_POST['password'];
						$zahaszowane_haslo = hash('sha3-512', $password); //zaszyfruj wpisane hasło
						
						$zapytanie_o_login = mysqli_query($connection, "SELECT * FROM users WHERE login='$login'"); //sprawdź czy wpisany login jest w bazie
						if (mysqli_num_rows($zapytanie_o_login) == 1)
						{
							$zapytanie_o_haslo = mysqli_query($connection, "SELECT * FROM users WHERE haslo='$zahaszowane_haslo'"); //jeśli tak, to sprawdź czy podane hasło jest w bazie
							
							if (mysqli_num_rows($zapytanie_o_haslo) == 1)
							{
								$wynik_zapytania_o_haslo = mysqli_fetch_assoc($zapytanie_o_haslo);
								
								//ustaw i pobierz odpowiednie dane potrzebne do określenia gdzie przekierować logującego się użytkownika
								$_SESSION['loggedin']=true;
								$id_usera = $wynik_zapytania_o_haslo['idu'];
								$_SESSION['nick'] = $wynik_zapytania_o_haslo['login'];
								$typ = $_SESSION['typ_usera'] = $wynik_zapytania_o_haslo['typ'];
								$czy_losowe = $_SESSION['czy_losowe'] = $wynik_zapytania_o_haslo['czy_losowe'];
								$error = 'brak';
								
								if ($czy_losowe == 0) //jeżeli hasło nie jest losowym ciągiem znaków, to ustal dane logującego się użytkownika
								{
									//dane o lokalizacji
									$ipaddress = $_SERVER["REMOTE_ADDR"];
									function ip_details($ip) 
									{
										$json = file_get_contents ("http://ipinfo.io/{$ip}/geo");
										$details = json_decode ($json);
										return $details;
									}
									
									$details = ip_details($ipaddress);
									$region = $details -> region;
									$kraj = $details -> country;
									$miasto = $details -> city;
									$lokalizacja = $details -> loc;
									$IP = $details -> ip;
								
									//dane o przeglądarce i systemie
									require_once "_Lib_UserAgentParser.php";
									$ua_info = parse_user_agent($HTTP_USER_AGENT);
									array('platform' => '[Detected Platform]','browser' => '[Detected Browser]','version' => '[Detected Browser Version]');
									$info_system_browser = $ua_info['browser']."/".$ua_info['version']."/".$ua_info['platform'];

									//wstawienie do bazy wszystkich danych poza tymi o rozdzielczości
					
									mysqli_query($connection, "INSERT INTO logowania_uzytkownikow (user,IP,lokalizacja,panstwo,region,miasto,system_przegladarka) VALUES ('$id_usera','$IP','$lokalizacja','$kraj','$region','$miasto','$info_system_browser')");
					
									//dane o rozdzielczości - wysyłane dzięki AJAX-owi do innego skrypu PHP
			
									echo '<script> 
					
											var screen_width = screen.width;
											var screen_height = screen.height;
											var window_inner_width = window.innerWidth;
											var window_inner_height = window.innerHeight;
											var xhttp = new XMLHttpRequest();
							
											xhttp.open("GET", "rozdzielczosc.php?q1=" + screen_width +  "&q2=" + screen_height + "&q3=" + window_inner_width + "&q4=" + window_inner_height, true);
											xhttp.send();
										</script>';
								}
							
								if ($czy_losowe == 1) //jeżeli hasło jest losowym ciągiem znaków, to przekieruj do panelu zmiany hasła
								{
									echo '<script>location.href="zmiana_hasla_losowego.php";</script>';
								}
								else if ($typ=='ADMINISTRATOR') // jeżeli logujący się użytkownik to admin, to przekieruj do panelu admina
								{
									echo '<script>location.href="ADMIN/portal_admin.php";</script>';
								}
								else // jeśli nie jest to admin, to przekieruj do panelu usera
								{
									echo '<script>location.href="USER/portal.php";</script>';
								}
							}
						//jeśli dane logowania (login i/lub hasło) są niepoprawne, wyświetl odpowiedni komunikat	
							else
							{
								$error= '<span style="color:yellow"> Nieprawidłowy login i/lub hasło! </span>';
							}
						}
						else 
						{
							$error= '<span style="color:yellow"> Nieprawidłowy login i/lub hasło! </span>';
						}			
					}
					
					if($error != 'brak')
					{
						echo $error;
					}
?>
			</div>
			<a href="test.php">TEST</a>
		</div>
	</body>
</html>