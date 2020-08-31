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
		<title>Rejestr zdarzeń</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"/>
		<link rel="stylesheet" href="styl_admin.css" type="text/css"/> <!-- podpięcie zewnętrznego arkusza stylów CSS -->
		<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet"> <!-- ustalenie stylu czcionki -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> <!-- podpięcie stylu, dzięki któremu możliwe będzie stworzenie responsywnego menu -->
	</head>
	<body>
	<div class="container">
			<!--wyświetlenie informacji o zalogowanym użytkowniku-->
			<div class="header"> Zalogowany użytkownik: <span style="color:#F41127"><?php $_SESSION['CZY_USUNIETO']=false; $nick = $_SESSION['nick'];$typ=$_SESSION['typ_usera'];echo $nick.' ('.$typ.')';?></span></div>
			<div class="menu" id="MENU"> <!-- pełna wersja menu dla admina -->
				<ol>
					<li> <a href="portal_admin.php"> Strona główna </a></li>
					<li> <a href="#"> Zarządzanie użytkownikami &#9660 </a>
						<ul>
							<li><a href="rejestracja_nowego_usera.php"> Dodanie nowego użytkownika </a></li>
							<li><a href="edycja_userow.php"> Edycja kont użytkowników </a> </li>
							<li><a href="usuwanie_userow.php"> Usuwanie kont użytkowników </a> </li>
						</ul>
					</li>
					<li> <a href="logowania_uzytkownikow.php"> Logowania użytkowników </a></li>
					<li class="active"> <a href="rejestr_zdarzen.php"> Rejestr zdarzeń </a></li>
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
			
			<div class="content" style="color:#000000;">
			Wyświetl dane według <br>
			<select id="filtr1"> <!-- wybór zdarzenia -->
							<option disabled selected>Rodzaj zdarzenia</option>
							<option value="reset"> Reset systemu </option>
							<option value="potwierdz"> Potwierdzenie alarmu </option>
							<option value="alarm_pierwszego_stopnia"> Alarm pierwszego stopnia </option>
							<option value="alarm_drugiego_stopnia"> Alarm drugiego stopnia </option>
			</select>
			<select id="filtr2"> <!-- wybór przedziału czasowego -->
							<option disabled selected>Data</option>
							<option value="tydzien">Ostatni tydzień</option>
							<option value="dwa_tygodnie">Ostatnie dwa tygodnie</option>
							<option value="miesiac">Ostatni miesiąc</option>
							<option value="trzy_miesiace">Ostatnie trzy miesiące</option>
							<option value="pol_roku">Ostatnie pół roku</option>
							<option value="rok">Ostatni rok</option>
			</select> <br>
			<input type="submit" value="Wyświetl" onclick="show_data()"> <!-- zatwierdzenie wybranych parametrów -->
			
			</div>
			
			<div id="tabela" style="font-size: 20px; padding-top: 20px;">
	
			<script>
				var dane = '0'; //początkowa zawartość danych do wyświetlenia
				function show_data() //funkcja, dzięki której możliwe jest wyświetlenie pożądanych danych
				{
					var ZDARZENIE = document.getElementById("filtr1").value; //odczyt wybranego zdarzenia
					var DATA = document.getElementById("filtr2").value; //odczyt wybranego przedziału czasowego
					
					//sprawdź, czy wybrano oba kryteria
					if (ZDARZENIE=='Rodzaj zdarzenia' && DATA=='Data')
					{
						document.getElementById("tabela").innerHTML = '<span style="color:red; font-size:30px;">Nie wybrano żadnych kryteriów!</span>';
					}
					else if (ZDARZENIE=='Rodzaj zdarzenia' || DATA=='Data')
					{
						document.getElementById("tabela").innerHTML = '<span style="color:red; font-size:30px;">Wybrano tylko jeden parametr!</span>';
					}
					else //jeśli wybrano oba parametry, to...
					{
						var xmlhttp = new XMLHttpRequest();
						xmlhttp.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200)
							{
								dane = this.responseText; //odbierz żądane dane i wyświetl je div'ie o podanej nazwie
								document.getElementById("tabela").innerHTML = dane;
							}
						};
						xmlhttp.open("GET", "pobierz_dane_zdarzen.php?q1=" + ZDARZENIE + "&q2=" + DATA, true); //wywołaj skrypt o podanej nazwie i prześlij do niego wybrane zdarzenie i przedział czasowy
						xmlhttp.send();
					}
				}
			</script>
			</div>
	</body>
</html>