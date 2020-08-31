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
		<title>Ustawienia systemu</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
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
					<li> <a href="ustaw_plan.php"> Ustaw plan budynku </a></li>
					<li class="active"> <a href="ustawienia_systemu.php"> Ustawienia systemu </a></li>
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
			
			<div id="tabela">
				<?php
					//pobierz dane bazy, z którą chcesz się połączyć
					require_once "connect.php";
					$connection = mysqli_connect($host, $user, $password);
					mysqli_query($connection, "SET CHARSET utf8");
					mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
					mysqli_select_db($connection, $db);
					
					//pobranie informacji o uszkodzonych czujnikach
					$SQL1 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_plomienia'");
					$WYNIK1 = mysqli_fetch_assoc($SQL1);
					$checkbox1 = $WYNIK1['czy_uszkodzona'];
					$aktualna_temp = $WYNIK1['temperatura_graniczna'];
		
					$SQL2 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_dymu'");
					$WYNIK2 = mysqli_fetch_assoc($SQL2);
					$checkbox2 = $WYNIK2['czy_uszkodzona'];
		
					$SQL3 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='rop'");
					$WYNIK3 = mysqli_fetch_assoc($SQL3);
					$checkbox3 = $WYNIK3['czy_uszkodzona'];
		
					$SQL4 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_temperatury'");
					$WYNIK4 = mysqli_fetch_assoc($SQL4);
					$checkbox4 = $WYNIK4['czy_uszkodzona'];

					//pobranie aktualnie ustawionego czasu do alarmu drugiego stopnia
					$SQL5 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_dymu'");
					$WYNIK5 = mysqli_fetch_assoc($SQL5);
					$aktualne_sekundy = $WYNIK5['czas_do_alarmu2'];
					$aktualne_minuty = floor($aktualne_sekundy / 60);
					$aktualne_sekundy = $aktualne_sekundy - $aktualne_minuty * 60
				?>
				
<!-- ************************************************************ Uszkodzone czujniki ************************************************************* -->

				<h2>Uszkodzone czujki</h2>
				<form method="post"> <!-- formularz zatwierdzający zmiany w systemie -->
				
<!-- ************************************************************* Czujnik płomienia ************************************************************** -->

					<input type="checkbox" id="checkbox1" class="checkbox_plomien" name="czujnik_plomienia" value=<?php if($checkbox1==0){echo 'on';} else {echo 'off';} ?>><label for="checkbox1" id="label_plomien" style="font-size:16px;"> <!-- własny checkbox dla czujnika płomienia -->
					<?php
						if ($checkbox1==1) //jeśli pobrana informacja świadczy o tym, że czujnik płomienia jest uszkodzony, to wyświetl odhaczony checkbox
						{
								echo '<style>
								
										#label_plomien:before
										{
											content: "\2713";
											color: #ffffff;
											line-height: 16px;
											text-align: center;
										}
										
										.checkbox_plomien:checked + #label_plomien:before
										{
											content: "";
										}
								</style>';
						}
						else //w przeciwnym razie wyświetl pusty checkbox
						{
							echo '<style>
								
										#label_plomien:before
										{
											content: "";
											color: #ffffff;
											line-height: 16px;
											text-align: center;
										}
										
										.checkbox_plomien:checked + #label_plomien:before
										{
											content: "\2713";
										}
								</style>';
						}
					?> Czujnik płomienia </label> &nbsp
					
<!-- ************************************************************* Czujnik dymu ****************************************************************** -->
						
					<input type="checkbox" id="checkbox2" class="checkbox_dym" name="czujnik_dymu" value=<?php if($checkbox2==0){echo 'on';} else {echo 'off';} ?>><label for="checkbox2" id="label_dym" style="font-size:16px;"> <!-- własny checkbox dla czujnika dymu -->
					<?php 
						if ($checkbox2==1) //jeśli pobrana informacja świadczy o tym, że czujnik dymu jest uszkodzony, to wyświetl odhaczony checkbox
						{
							echo '<style>
								
										#label_dym:before
										{
											content: "\2713";
											color: #ffffff;
											line-height: 16px;
											text-align: center;
										}
										
										.checkbox_dym:checked + #label_dym:before
										{
											content: "";
										}
								</style>';
						}
						else //w przeciwnym razie wyświetl pusty checkbox
						{
							echo '<style>
								
										#label_dym:before
										{
											content: "";
											color: #ffffff;
											line-height: 16px;
											text-align: center;
										}
										
										.checkbox_dym:checked + #label_dym:before
										{
											content: "\2713";
										}
								</style>';
						}
					?> Czujnik dymu </label> &nbsp
					
<!-- **************************************************************** ROP ************************************************************************* -->
						
					<input type="checkbox" id="checkbox3" class="checkbox_rop" name="rop" value=<?php if($checkbox3==0){echo 'on';} else {echo 'off';} ?>><label for="checkbox3" id="label_rop" style="font-size:16px;"> <!-- własny checkbox dla ROP-u -->
					<?php
						if ($checkbox3==1) //jeśli pobrana informacja świadczy o tym, że ROP jest uszkodzony, to wyświetl odhaczony checkbox
						{
							echo '<style>
								
										#label_rop:before
										{
											content: "\2713";
											color: #ffffff;
											line-height: 16px;
											text-align: center;
										}
										
										.checkbox_rop:checked + #label_rop:before
										{
											content: "";
										}
								</style>';
						}
						else //w przeciwnym razie wyświetl pusty checkbox
						{
							echo '<style>
								
										#label_rop:before
										{
											content: "";
											color: #ffffff;
											line-height: 16px;
											text-align: center;
										}
										
										.checkbox_rop:checked + #label_rop:before
										{
											content: "\2713";
										}
								</style>';
						}
					?> ROP </label> &nbsp
					
<!-- *********************************************************** Czujnik temperatury ************************************************************** -->
						
					<input type="checkbox" id="checkbox4" class="checkbox_temperatura" name="czujnik_temperatury" value=<?php if($checkbox4==0){echo 'on';} else {echo 'off';} ?>><label for="checkbox4" id="label_temperatura" style="font-size:16px;"> <!-- własny checkbox dla czujnika temperatury -->
					<?php
						if ($checkbox4==1) ////jeśli pobrana informacja świadczy o tym, że czujnik temperatury jest uszkodzony, to wyświetl odhaczony checkbox
						{
							echo '<style>
								
										#label_temperatura:before
										{
											content: "\2713";
											color: #ffffff;
											line-height: 16px;
											text-align: center;
										}
										
										.checkbox_temperatura:checked + #label_temperatura:before
										{
											content: "";
										}
								</style>';
						}
						else //w przeciwnym razie wyświetl pusty checkbox
						{
							echo '<style>
								
										#label_temperatura:before
										{
											content: "";
											color: #ffffff;
											line-height: 16px;
											text-align: center;
										}
										
										.checkbox_temperatura:checked + #label_temperatura:before
										{
											content: "\2713";
										}
								</style>';
						}
					?> Czujnik temperatury </label>		<br /> <br />
					
<!-- ******************************************************* Czas do alarmu drugiego stopnia ****************************************************** -->
				
				<!-- wyświetl aktualnie ustawiony czas do alarmu drugiego stopnia -->
				<h2> Aktualny czas do alarmu drugiego stopnia (min. 0 minut i 20 sekund, max. 15 minut): <br>
				Minut: <?php echo '<span style="color:#0A1EA7">'.$aktualne_minuty.'</span>'; ?> &nbsp Sekund: <?php echo '<span style="color:#0A1EA7">'.$aktualne_sekundy.'</span>'; ?></h2>
				
				<!-- pola wyboru minut i sekund, aby ustawić nowy czas do alarmu drugiego stopnia -->
				<select name="minuty"/>
				<option disabled="" selected>Liczba minut</option>
				<?php 
					for ($i=0;$i<=15;$i++) {
						echo '<option>'.$i.'</option>';
					}
				?>
				</select> &nbsp &nbsp
				<select name="sekundy"/>
				<option disabled="" selected>Liczba sekund</option>
				<?php 
					for ($i=0;$i<60;$i++) {
						echo '<option>'.$i.'</option>';
					}
				?>
				</select> <br >
				
<!-- *********************************************************** Temperatura graniczna ************************************************************ -->
				
				<!-- wyświetl aktualnie ustawioną temperaturę graniczną -->
				<h2> Aktualna temperatura graniczna: <br>
				<?php echo '<span style="color:#0A1EA7">'.$aktualna_temp.'</span> &degC'; ?></h2>

				<!-- pole do ustawienia nowej wartości temperatury granicznej -->
				<select name="temperatura"/>
				<option disabled="" selected>Temperatura graniczna</option>
				<?php 
					for ($i=70;$i<=100;$i+=5) {
						echo '<option>'.$i.'&degC</option>';
					}
				?>
				</select> <br ><br >

				<input type="submit" value="Zapisz zmiany" name="button"/> <!-- przycisk zatwierdzający wprowadzone zmiany -->
				</form> <br />
				
				<?php
					
					if (isset($_POST['button'])) //jeśli zatwierdzono zmiany, to...
					{
						//pobierz ustawione dane z pól
						$uszkodzona1 = $_POST['czujnik_plomienia'];
						$uszkodzona2 = $_POST['czujnik_dymu'];
						$uszkodzona3 = $_POST['rop'];
						$uszkodzona4 = $_POST['czujnik_temperatury'];
						$minuty = $_POST['minuty'];
						$sekundy = $_POST['sekundy'];
						$temperatura = $_POST['temperatura'];
						
						//wstaw do bazy odpowiednie dane o uszkodzonych czujnikach - zgodnie z tym, które odhaczono, a które nie (on - zaznaczono, więc czujnik uszkodzony; off - odhaczono, więc czujnik sprawny)
						if ($uszkodzona1=='off')
						{
							mysqli_query ($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona='0' WHERE czujka='czujka_plomienia'");
						}
						else if ($uszkodzona1=='on')
						{
							mysqli_query ($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona='1' WHERE czujka='czujka_plomienia'");
						}
						
						if ($uszkodzona2=='off')
						{
							mysqli_query ($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona='0' WHERE czujka='czujka_dymu'");
						}
						else if ($uszkodzona2=='on')
						{
							mysqli_query ($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona='1' WHERE czujka='czujka_dymu'");
						}
						
						if ($uszkodzona3=='off')
						{
							mysqli_query ($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona='0' WHERE czujka='rop'");
						}
						else if ($uszkodzona3=='on')
						{
							mysqli_query ($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona='1' WHERE czujka='rop'");
						}
						
						if ($uszkodzona4=='off')
						{
							mysqli_query ($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona='0' WHERE czujka='czujka_temperatury'");
						}
						else if ($uszkodzona4=='on')
						{
							mysqli_query ($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona='1' WHERE czujka='czujka_temperatury'");
						}
						
						
						//wstaw do bazy ustawiony czas
						//$czas = 0;
						if ($minuty == 15) //jeśli ustawiony czas jest równy lub przekracza 15 min, to ustaw czas jako 15 minut
						{
							$czas = $minuty * 60;
						}
						else if (($minuty == 0 && $sekundy >= 20) || $minuty != 0) //jeśli ustawiony czas jest dłuższy od 0 minut i 20 sekund to przelicz ten czas na sekundy i wstaw te dane do bazy
						{
							$czas = $minuty * 60 + $sekundy;
						}
						else if ($minuty == 0 && $sekundy < 20 && $sekundy > 0) //jeśli ustawiony czas jest krótszy niż 20 sekund, to wstaw do bazy 20 sekund
						{
							$czas = 20;
						}
						//wstaw zaktualizowany czas do bazy
						if ($czas != 0) 
						{
							mysqli_query($connection, "UPDATE ustawienia_systemu SET czas_do_alarmu2='$czas' LIMIT 2");
						}
						
						//wstaw do bazy ustawioną wartość temperatury granicznej
						if ($temperatura != 0) //jeśli ustawioną nową wartość, to wstaw ją do bazy w odpowiednie miejsce
						{
							mysqli_query($connection, "UPDATE ustawienia_systemu SET temperatura_graniczna='$temperatura' WHERE idcz=1");
						}
				
						echo '<script> location.href="ustawienia_systemu.php"</script>';
					}
				?>	
	</div>
	</body>
</html>