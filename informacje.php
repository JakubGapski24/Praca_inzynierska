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
		<title>Informacje o systemie</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"/>
		<link rel="stylesheet" href="styl_user.css" type="text/css"/> <!-- podpięcie zewnętrznego arkusza stylów CSS -->
		<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet"> <!-- ustalenie stylu czcionki -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> <!-- podpięcie stylu, dzięki któremu możliwe będzie stworzenie responsywnego menu -->
	</head>
	<body>
	<div class="container">
			<!--wyświetlenie informacji o zalogowanym użytkowniku-->
			<div class="header"> Zalogowany użytkownik: <span style="color:#F41127"><?php $nick = $_SESSION['nick'];$typ=$_SESSION['typ_usera'];echo $nick.' ('.$typ.')';?></span></div>
			<div class="menu" id="MENU"> <!-- pełna wersja menu dla admina -->
				<ol>
					<li> <a href="portal.php"> Strona główna </a></li>
					<li> <a href="konto.php"> Informacje o koncie </a></li>
					<li> <a href="logowania.php"> Logowania </a></li>
					<li class="active"> <a href="informacje.php"> Informacje o systemie </a></li>
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
			
<!-- **************************************************** wyświetl elementy systemu ************************************************************** -->
				
			<div id="tabela"  style="font-size: 20px; padding-top: 20px;">
	
				<h2 style="color:#000000"> Elementy systemu </h2>
					<table width="95%" align="center" border="1" bordercolor="#303030"  cellpadding="0" cellspacing="0"> 
					<tr>
			<?php
				//pobierz dane bazy, z którą chcesz się połączyć
				require_once "connect.php";
				$connection = mysqli_connect($host, $user, $password);
				mysqli_query($connection, "SET CHARSET utf8");
				mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
				mysqli_select_db($connection, $db);
			
				$tresc_zapytania = "SELECT * FROM elementy_systemu";			
				$zapytanie_o_dane = mysqli_query($connection, $tresc_zapytania);
				if (mysqli_num_rows($zapytanie_o_dane) >= 1) //wyświetl nazwy kolumn tabeli
				{
					echo<<<END
					<td style="position:sticky;top:73px;" width="40" align="center" bgcolor="e5e5e5">Element</td>
					<td style="position:sticky;top:73px;" width="40" align="center" bgcolor="e5e5e5">Nazwa</td>
					<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Zdjęcie</td>
					<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Opis</td>
					</tr><tr>
END;
		
				}
			
				for ($i=1; $i<=mysqli_num_rows($zapytanie_o_dane); $i++) //dopóki są rekordy w tej tabeli, to wyświetlaj dane (czyli pokaż wszystkie elementy)
				{
					$wynik = mysqli_fetch_assoc($zapytanie_o_dane);
					$id = $wynik['ide'];
					$nazwa = $wynik['nazwa'];
					$nazwa_zdjecia = $wynik['zdjecie'];
					$zdjecie = '<img src="\images/'.$nazwa_zdjecia.'" width="150" height="150">';
					$opis = $wynik['opis'];
				
					echo<<<END
					<td width="40" align="center">$i</td>
					<td width="40" align="center">$nazwa</td>
					<td width="60" align="center">$zdjecie</td>
					<td width="60" align="center">$opis</td>
					</tr><tr>
END;
				}
			?>

			</tr></table>	
			
			</div>	
	</div>
	</body>	