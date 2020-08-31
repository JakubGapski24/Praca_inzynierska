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
		<title>Logowania</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
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
					<li class="active"> <a href="logowania.php"> Logowania </a></li>
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
			Wyświetl swoje logowania <br>
			<select id="filtr"> <!-- wybór okresu -->
							<option disabled selected>Data</option>
							<option value="wszystko">Wszystko</option>
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
					var DATA = document.getElementById("filtr").value; //odczyt wybranego przedziału czasowego
					var USER = "<?php echo $nick; ?>"; //ustaw login zalogowanego usera
					
					if (DATA=='Data') //sprawdź, czy wybrano jakiś przedział czasowy
					{
						document.getElementById("tabela").innerHTML = '<span style="color:red; font-size:30px;">Nie wybrano żadnych kryteriów!</span>';
					}
					else
					{
						var xmlhttp = new XMLHttpRequest();
						xmlhttp.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200)
							{
								dane = this.responseText; //odbierz żądane dane i wyświetl je div'ie o podanej nazwie
								document.getElementById("tabela").innerHTML = dane;
							}
						};
						xmlhttp.open("GET", "pobierz_dane_logowan.php?q1=" + USER + "&q2=" + DATA, true); //wywołaj skrypt o podanej nazwie i prześlij do niego login zalogowanego użytkownika i przedział czasowy
						xmlhttp.send();
					}
				}
			</script>
			</div>
	</body>
</html>