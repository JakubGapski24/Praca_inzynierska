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
		<title>Informacje o koncie</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"/>
		<link rel="stylesheet" href="styl_user.css" type="text/css"/> <!-- podpięcie zewnętrznego arkusza stylów CSS -->
		<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet"> <!-- ustalenie stylu czcionki -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> <!-- podpięcie stylu, dzięki któremu możliwe będzie stworzenie responsywnego menu -->
	</head>
	<body>
	<div class="container">
			<!-- wyświetlenie informacji o zalogowanym użytkowniku -->
			<div class="header"> Zalogowany użytkownik: <span style="color:#F41127"><?php $login = $_SESSION['nick'];$typ=$_SESSION['typ_usera'];echo $login.' ('.$typ.')';?></span></div>
			<div class="menu" id="MENU"> <!-- pełna wersja menu dla usera -->
				<ol>
					<li> <a href="portal.php"> Strona główna </a></li>
					<li class="active"> <a href="konto.php"> Informacje o koncie </a></li>
					<li> <a href="logowania.php"> Logowania </a></li>
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
				
		<!-- **************************************************** wyświetl swoje dane ************************************************************ -->

			<div id="tabela" style="font-size: 20px; padding-top: 20px;">
				
				<h2 style="color:#000000"> Dane użytkownika: <?php echo '<span style="color:red">'.$login.'</span>'; ?> </h2>
					<table width="95%" align="center" border="1" bordercolor="#303030"  cellpadding="0" cellspacing="0"> 
					<tr>
				<?php
					//pobierz dane bazy, z którą chcesz się połączyć
					require_once "connect.php";
					$connection = mysqli_connect($host, $user, $password);
					mysqli_query($connection, "SET CHARSET utf8");
					mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
					mysqli_select_db($connection, $db);
					
					$zapytanie1 = mysqli_query($connection,"SELECT * FROM users WHERE login='$login'");
				
				if (mysqli_num_rows($zapytanie1) >= 1) //wyświetl nazwy kolumn tabeli
				{
					echo<<<END
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Login</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Adres e-mail</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Typ użytkownika</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Ostatnia modyfikacja</td>
					<td style="position:sticky;top:73px;" width="150" align="center" bgcolor="e5e5e5">Admin dokonujący modyfikacji</td>
					</tr><tr>
END;
				}
				
				for ($i=1; $i<=mysqli_num_rows($zapytanie1); $i++) //dopóki są rekordy w tej tabeli, to wyświetlaj dane (powinien być jeden rekord, bo to dane konkretnego usera)
				{
					$wynik1 = mysqli_fetch_assoc($zapytanie1);
					$login_z_bazy = $wynik1['login'];
					$email = $wynik1['email'];
					$typ = $wynik1['typ'];
					$data = $wynik1['ostatnia_modyfikacja'];
					$kto = $wynik1['kto'];
					
						
							echo<<<END
							<td width="150" align="center">$login_z_bazy</td>
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
</div>
	</div>
	</body>
</html>