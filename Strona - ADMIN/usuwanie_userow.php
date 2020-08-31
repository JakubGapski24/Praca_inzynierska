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
		<title>Usuwanie użytkowników</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
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
							<li><a href="edycja_userow.php"> Edycja kont użytkowników </a> </li>
							<li class="active"><a href="usuwanie_userow.php"> Usuwanie kont użytkowników </a> </li>
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
			
			<?php
				if ($_SESSION['usuniety_user']!='' && $_SESSION['CZY_USUNIETO']) //jeżeli usunięto usera, to wyświetl odpowiedni komunikat
				{
					echo '<div class="content">
							<span style="color:black;">Usunięto konto użytkownika o loginie: </span>'.$_SESSION['usuniety_user'].'
						</div>';
				}
			?>
				
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
				
				$zapytanie1 = mysqli_query($connection,"SELECT * FROM users");
				
				if (mysqli_num_rows($zapytanie1) >= 1) //wyświetl nazwy kolumn tabeli
				{
					echo<<<END
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Login</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Adres e-mail</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Typ użytkownika</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Ostatnia modyfikacja</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Administrator dokonujący modyfikacji</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Usuń wybrane konto</td>
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
					
						//ostatnia kolumna to przyciski wyboru, z których każdy jest unikatowy
							echo<<<END
							<td width="150" align="center">$login</td>
							<td width="150" align="center">$email</td>
							<td width="150" align="center">$typ</td>
							<td width="150" align="center">$data</td>
							<td width="150" align="center">$kto</td>
							<td width="150" align="center"><input type="radio" id="radio$i" class="radio" name="usuniecie" value="$login" onclick="usun_usera()"><label for="radio$i" style="height:16px;"></label></td>
							</tr><tr>
END;
				}	
		?>
		</tr></table>
			<script>
						function usun_usera() { //funkcja umożliwiająca usunięcie wybranego konta
								var konto = document.querySelector('input[name="usuniecie"]:checked').value; //pobierz, odczytaj wartość klikniętego przycisku typu 'radio' - wybierz użytkownika do usunięcia
								var czy_usunieto = 'NIE';
								//przesuń stronę na samą górę
								document.body.scrollTop = 0;
								document.documentElement.scrollTop = 0;
										var xmlhttp = new XMLHttpRequest();
										xmlhttp.onreadystatechange = function() { //za pomocą AJAX'a prześlij login do podanego skryptu PHP, tam usuń konto i odbierz informację zwrotną o powodzeniu całej operacji
											if (this.readyState == 4 && this.status == 200) {
												czy_usunieto = this.responseText;
												if (czy_usunieto=='TAK')
												{
													location.href = "usuwanie_userow.php"; //jeśli konto zostało pomyślnie usunięte, to odśwież stronę
												}
											}
										};
										xmlhttp.open("GET", "usun_usera.php?konto=" + konto, true); //wyślij wybrany login
										xmlhttp.send();
						}
		</script>

</div>
	</div>
	</body>
</html>