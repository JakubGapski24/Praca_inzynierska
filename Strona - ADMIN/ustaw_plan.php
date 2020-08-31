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
		<title>Ustaw plan budynku</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
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
					<li> <a href="rejestr_zdarzen.php"> Rejestr zdarzeń </a></li>
					<li class="active"> <a href="ustaw_plan.php"> Ustaw plan budynku </a></li>
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
				<form method="POST" ENCTYPE="multipart/form-data">  <!-- formularz wstawiania nowego pliku (planu budynku) -->
					<input type="file" id="file" class="file" name="plik" onchange="change()"><label for="file" id="label_file">Wybierz plik</label><br> <!-- pole na wybranie pliku do wstawienia -->
					<input type="submit" value="Wstaw plan budynku" name="button"/> <!-- przycisk zatwierdzający wstawianie nowego planu -->
				</form> <br />
				
				<script>
					function change() { //funkcja, dzięki której możliwe jest zobaczenie nazwy wybranego pliku
						var napis = document.getElementById("file").value;
						if (napis == '') //jeśli nie wybrano pliku pokaż polecenie "wybierz plik"
						{
							document.getElementById("label_file").innerHTML = 'Wybierz plik';
						}
						else //w przeciwnym wypadku wyświetl nazwę pliku (bez ścieżki, samą nazwę)
						{
							var bez_sciezki = napis.split(/(\\|\/)/g).pop();
							document.getElementById("label_file").innerHTML = bez_sciezki;
						}
					};
				</script>
				
				<?php
					//pobierz dane bazy, z którą chcesz się połączyć
					require_once "connect.php";
					$connection = mysqli_connect($host, $user, $password);
					mysqli_query($connection, "SET CHARSET utf8");
					mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
					mysqli_select_db($connection, $db);
					//pobierz nazwę pliku z bazy danych i znajdź plik o podanej nazwie na serwerze
					$zapytanie_o_plan = mysqli_query($connection, "SELECT * FROM plan_budynku");
					$wynik = mysqli_fetch_assoc($zapytanie_o_plan);
					$plan = $wynik['nazwa'];
					if (isset($_POST['button']))
					{
						$max_rozmiar = 5000000; //maksymalny dopuszczalny rozmiar wrzucanego pliku w bajtach
						$plik = $_FILES['plik']['name'];
						if (is_uploaded_file($_FILES['plik']['tmp_name'])) //sprawdzenie, czy podany został jakikolwiek plik
						{
							if ($_FILES['plik']['size'] > $max_rozmiar) //sprawdzenie, czy wrzucany plik nie przekracza ustalonego rozmiaru
							{
								echo '<span style="color:red;">Zbyt duży plik! Dopuszczalny rozmiar pliku to 5MB!</span>';
							}
							else if ($_FILES['plik']['type'] != 'image/jpeg' && $_FILES['plik']['type'] != 'image/jpg' && $_FILES['plik']['type'] != 'image/png') //sprawdzenie, czy wrzucany plik ma odpowiedni format (jpg, jpeg lub png)
							{
								echo '<span style="color:red;">Zły format! Plik musi mieć format JPEG,JPG lub PNG!</span>';
							}
							else
							{ //jeśli wszystko jest w porządku, to...
								unlink("/images/$plan"); //usuń poprzedni plan budynku z serwera
								move_uploaded_file($_FILES['plik']['tmp_name'], '/images/'.$plik); //dodaj na serwer żądany plik i zapisz jego nazwę w bazie danych
								$wstaw = mysqli_query($connection, "UPDATE plan_budynku SET nazwa='$plik'");
								if ($wstaw && $_FILES['plik']['error'] == 0)
								{
									echo 'Plik: '.$plik.' został odebrany poprawnie';
								}
								else
								{
									echo '<span style="color:red;">Wystąpił błąd w odbieraniu pliku!</span>';
								}
							}
						}
						else //jeśli zatwierdzono wrzucanie pliku, ale nie wybrano żadnego pliku, to wyświetl odpowiedni komunikat
						{
							echo '<span style="color:red;">Nie wybrano żadnego pliku!</span>';
						} 
					}	
				?>
				<div id="plan">
				<h3> Aktualny plan budynku </h3>
				<?php
					//wyświetl aktualny plan budynku
					$zapytanie_o_plan = mysqli_query($connection, "SELECT * FROM plan_budynku");
					$wynik = mysqli_fetch_assoc($zapytanie_o_plan);
					$plan = $wynik['nazwa'];
					echo '<img src="\images/'.$plan.'" width="70%" height="70%">';
				?>
				</div>
			</div>
	</body>
</html>