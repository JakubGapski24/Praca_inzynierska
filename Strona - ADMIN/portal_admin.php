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
		<title>Strona główna</title> <!-- nadanie tytułu widocznego na zakładce w przeglądarce -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"/>
		<link rel="stylesheet" href="styl_admin.css" type="text/css"/> <!-- podpięcie zewnętrznego arkusza stylów CSS -->
		<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet"> <!-- ustalenie stylu czcionki -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> <!-- podpięcie stylu, dzięki któremu możliwe będzie stworzenie responsywnego menu -->
		<!-- podpięcie bibliotek odpowiedzialnych za przesuwanie elementów na ekranie i responsywność tych elementów -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script src="jQuery.js"></script>
		<script src="Draggable.js"></script>
		<script src="lodash.js"></script>
		<script src="TweenMax.js"></script>
		<script src="ThrowPropsPlugin.js"></script>
	</head>
	<body>
	
	<?php
	//********************************************** Wykrywanie nowego rekordu w bazie ***************************************************************
	
				$_SESSION['CZY_USUNIETO'] = false; //dzięki temu parametrowi, możliwe jest prawidłowe wyświetlanie komunikatu o usunięciu danego użytkownika w podstronie "usuwanie userów"
				
				//pobierz dane bazy, z którą chcesz się połączyć
				require_once "connect.php";
				$connection = mysqli_connect($host, $user, $password);
				mysqli_query($connection, "SET CHARSET utf8");
				mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
				mysqli_select_db($connection, $db);
				//pobierz ostatni rekord z bazy - najnowsze stany systemu
				$zapytanie1 = mysqli_query($connection,"SELECT * FROM stany_wyprowadzen ORDER BY id DESC LIMIT 1");
                $wynik1 = mysqli_fetch_assoc($zapytanie1);
				$dataOs = strtotime($wynik1['data_ostatniego_laczenia']); //zamień datę ostatniego łączenia systemu z serwerem na sekundy
				
	//pobrana z ostatniego rekordu bazy data ostatniego łączenia się systemu z serwerem jest porównywana z datą pobraną w dokładnie ten sam sposób metodą AJAX, jeśli się różnią, to znaczy, że wstawiono nowe dane i należy odświeżyć stronę
				echo '<script>
				var stara_data = '.$dataOs.';
				var nowa_data = 0;
				var jakas_zmienna = setInterval(function() {
						var xmlhttp = new XMLHttpRequest();
						xmlhttp.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200) {
								nowa_data = this.responseText;
								if (nowa_data - stara_data != 0)
								{
									location.href="portal_admin.php";
								}
							}
						};
						xmlhttp.open("GET", "nowa_data.php?", true);
						xmlhttp.send();
					
				}, 500);
				</script>';
	?>
		
	<?php	
	//******************************************** Przesuwanie czujników po ekranie i ich responsywność **********************************************
	
			//pobranie pozycji, współrzędnych(x,y) poszczególnych czujek na ekranie
			$ZAPYTANIE1 = mysqli_query($connection, "SELECT * FROM pozycje_czujek_na_ekranie WHERE czujka='czujka plomienia'");
			$WYNIK1 = mysqli_fetch_assoc($ZAPYTANIE1);
			$od_gory1 = $WYNIK1['od_gory'];
			$od_lewej1 = $WYNIK1['od_lewej'];
							
			$ZAPYTANIE2 = mysqli_query($connection, "SELECT * FROM pozycje_czujek_na_ekranie WHERE czujka='czujka dymu'");
			$WYNIK2 = mysqli_fetch_assoc($ZAPYTANIE2);
			$od_gory2 = $WYNIK2['od_gory'];
			$od_lewej2 = $WYNIK2['od_lewej'];
							
			$ZAPYTANIE3 = mysqli_query($connection, "SELECT * FROM pozycje_czujek_na_ekranie WHERE czujka='rop'");
			$WYNIK3 = mysqli_fetch_assoc($ZAPYTANIE3);
			$od_gory3 = $WYNIK3['od_gory'];
			$od_lewej3 = $WYNIK3['od_lewej'];
							
			$ZAPYTANIE4 = mysqli_query($connection, "SELECT * FROM pozycje_czujek_na_ekranie WHERE czujka='czujka temperatury'");
			$WYNIK4 = mysqli_fetch_assoc($ZAPYTANIE4);
			$od_gory4 = $WYNIK4['od_gory'];
			$od_lewej4 = $WYNIK4['od_lewej'];
	?>

	<script>

		//skrypt w JS odpowiedzialny za przesuwanie elementów po ekranie i responsywność tych elementów
	
	//********************************************************** Czujnik płomienia *******************************************************************
		$(() => {
		  var xPercent = $(0);
		  var yPercent = $(0);
		  
		  //stwórz nowy element zdolny do przesuwania na ekranie (stworzenie nowego obiektu)
		  var czujnik_plomienia = new CustomDraggable("#czujnik_plomienia", {
			onDrag: updateHud,
			onThrowUpdate: updateHud,
			onThrowComplete: updateHud
		  });
		  
		  var od_gory = <?php echo $od_gory1; ?>;
		  var od_lewej = <?php echo $od_lewej1; ?>;
		  
		  czujnik_plomienia.positionRelative(od_lewej,od_gory); //wyświetlenie czujnika płomienia na ekranie zgodnie z pobraną pozycją
			
		  $(window).resize(_.debounce(updateHud, 30));  
		  updateHud();
		  
		  //przeliczenie położenia czujnika na '%'
		  function updateHud() {
			
			var xValue = czujnik_plomienia.isActive ? czujnik_plomienia.xPos / czujnik_plomienia.xMax * 100 : czujnik_plomienia.xPercent;
			var yValue = czujnik_plomienia.isActive ? czujnik_plomienia.yPos / czujnik_plomienia.yMax * 100 : czujnik_plomienia.yPercent;
				
			xPercent.text(Math.abs(xValue).toFixed(1));
			yPercent.text(Math.abs(yValue).toFixed(1));
			
			var xhttp = new XMLHttpRequest();
			
			//dla konkretnego czujnika, już po jego przemieszczeniu i puszczeniu klawisza myszki (lub po zabraniu palca, w przypadku ekranu dotykowego), wywoływany jest podany skrypt w PHP i wysyłane są do niego (a z niego do bazy danych) dane o nowej pozycji czujki 
		  
			xhttp.open("GET", "pozycje_czujek_na_ekranie.php?q1="+yValue + "&q2=" + xValue + "&q3=" + "czujnik_plomienia", true);
			xhttp.send();	
		  }
		});

	//************************************************************** Czujnik dymu ********************************************************************
		$(() => {
		  var xPercent = $(0);
		  var yPercent = $(0);
		  
		  //stwórz nowy element zdolny do przesuwania na ekranie (stworzenie nowego obiektu)
		  var czujnik_dymu = new CustomDraggable("#czujnik_dymu", {
			onDrag: updateHud,
			onThrowUpdate: updateHud,
			onThrowComplete: updateHud
		  });
		  
		  var od_gory = <?php echo $od_gory2; ?>;
		  var od_lewej = <?php echo $od_lewej2; ?>;
		  
		  czujnik_dymu.positionRelative(od_lewej,od_gory); //wyświetlenie czujnika dymu na ekranie zgodnie z pobraną pozycją
			
		  $(window).resize(_.debounce(updateHud, 30));  
		  updateHud();
		  
		  //przeliczenie położenia czujnika na '%'
		  function updateHud() {
			
			var xValue = czujnik_dymu.isActive ? czujnik_dymu.xPos / czujnik_dymu.xMax * 100 : czujnik_dymu.xPercent;
			var yValue = czujnik_dymu.isActive ? czujnik_dymu.yPos / czujnik_dymu.yMax * 100 : czujnik_dymu.yPercent;
				
			xPercent.text(Math.abs(xValue).toFixed(1));
			yPercent.text(Math.abs(yValue).toFixed(1));
			
			var xhttp = new XMLHttpRequest();
			
			//dla konkretnego czujnika, już po jego przemieszczeniu i puszczeniu klawisza myszki (lub po zabraniu palca, w przypadku ekranu dotykowego), wywoływany jest podany skrypt w PHP i wysyłane są do niego (a z niego do bazy danych) dane o nowej pozycji czujki  
		  
			xhttp.open("GET", "pozycje_czujek_na_ekranie.php?q1="+yValue + "&q2=" + xValue + "&q3=" + "czujnik_dymu", true);
			xhttp.send();
				
		  }
		});

	//****************************************************************** ROP *************************************************************************
		$(() => {
		  var xPercent = $(0);
		  var yPercent = $(0);
		  
		  //stwórz nowy element zdolny do przesuwania na ekranie (stworzenie nowego obiektu)
		  var rop = new CustomDraggable("#rop", {
			onDrag: updateHud,
			onThrowUpdate: updateHud,
			onThrowComplete: updateHud
		  });
		  
		  var od_gory = <?php echo $od_gory3; ?>;
		  var od_lewej = <?php echo $od_lewej3; ?>;
		  
		  rop.positionRelative(od_lewej,od_gory); //wyświetlenie ROP-u na ekranie zgodnie z pobraną pozycją
			
		  $(window).resize(_.debounce(updateHud, 30));  
		  updateHud();
		  
		  //przeliczenie położenia czujnika na '%'
		  function updateHud() {
			
			var xValue = rop.isActive ? rop.xPos / rop.xMax * 100 : rop.xPercent;
			var yValue = rop.isActive ? rop.yPos / rop.yMax * 100 : rop.yPercent;
				
			xPercent.text(Math.abs(xValue).toFixed(1));
			yPercent.text(Math.abs(yValue).toFixed(1));
			
			var xhttp = new XMLHttpRequest();
			
			//dla konkretnego czujnika, już po jego przemieszczeniu i puszczeniu klawisza myszki (lub po zabraniu palca, w przypadku ekranu dotykowego), wywoływany jest podany skrypt w PHP i wysyłane są do niego (a z niego do bazy danych) dane o nowej pozycji czujki  
		  
			xhttp.open("GET", "pozycje_czujek_na_ekranie.php?q1="+yValue + "&q2=" + xValue + "&q3=" + "rop", true);
			xhttp.send();
				
		  }
		});

	//*********************************************************** Czujnik temperatury ****************************************************************
		$(() => {
		  var xPercent = $(0);
		  var yPercent = $(0);
		  
		  //stwórz nowy element zdolny do przesuwania na ekranie (stworzenie nowego obiektu)
		  var czujnik_temperatury = new CustomDraggable("#czujnik_temperatury", {
			onDrag: updateHud,
			onThrowUpdate: updateHud,
			onThrowComplete: updateHud
		  });
		  
		  var od_gory = <?php echo $od_gory4; ?>;
		  var od_lewej = <?php echo $od_lewej4; ?>;
		  
		  czujnik_temperatury.positionRelative(od_lewej,od_gory); //wyświetlenie czujnika temperatury na ekranie zgodnie z pobraną pozycją
			
		  $(window).resize(_.debounce(updateHud, 30));  
		  updateHud();
		  
		  //przeliczenie położenia czujnika na '%'
		  function updateHud() {
			
			var xValue = czujnik_temperatury.isActive ? czujnik_temperatury.xPos / czujnik_temperatury.xMax * 100 : czujnik_temperatury.xPercent;
			var yValue = czujnik_temperatury.isActive ? czujnik_temperatury.yPos / czujnik_temperatury.yMax * 100 : czujnik_temperatury.yPercent;
				
			xPercent.text(Math.abs(xValue).toFixed(1));
			yPercent.text(Math.abs(yValue).toFixed(1));
			
			var xhttp = new XMLHttpRequest();
			
			//dla konkretnego czujnika, już po jego przemieszczeniu i puszczeniu klawisza myszki (lub po zabraniu palca, w przypadku ekranu dotykowego), wywoływany jest podany skrypt w PHP i wysyłane są do niego (a z niego do bazy danych) dane o nowej pozycji czujki  
		  
			xhttp.open("GET", "pozycje_czujek_na_ekranie.php?q1="+yValue + "&q2=" + xValue + "&q3=" + "czujnik_temperatury", true);
			xhttp.send();
				
		  }
		});

		//klasa dziedzicząca klasę Draggable - rozszerza ona klasę draggable w celu stworzenia responsywnego, przesuwalnego obiektu
		class CustomDraggable extends Draggable {
		  constructor(target, vars) {
			
			//wywołaj konstruktor dla klasy Draggable
			super(target);
			
			//skopiuj oryginalne parametry obiektu
			this._vars  = vars;
			this.parent = this.target.parentNode || document.body;
				
			//ustaw wybrane funkcje
			_.assign(this.vars, vars, {      
			  bounds          : vars.bounds || this.parent,
			  throwProps      : true,
			  onClick         : this.createInterceptor("onClick"),
			  onDrag          : this.createInterceptor("onDrag"),
			  onDragEnd       : this.createInterceptor("onDragEnd"),
			  onDragStart     : this.createInterceptor("onDragStart"),
			  onPress         : this.createInterceptor("onPress"),
			  onRelease       : this.createInterceptor("onRelease"),
			  onThrowComplete : this.createInterceptor("onThrowComplete"),
			  onThrowUpdate   : this.createInterceptor("onThrowUpdate")
			});
			
			this.transform = this.target._gsTransform;
			this.width     = this.target.offsetWidth;
			this.height    = this.target.offsetHeight;
				
			//odczytaj, ustaw pozycję diva, w którym będą przesuwane elementy
			$(window).resize(e => this.updateBounds());
			this.updateBounds();
			
			//kiedy rozpocznie się przesuwanie
			this.isActive = false;
			
			_.bindAll(this);
		  }
		  
		  // Returns the parent's width|height - the target's width|height
		  get xMax() { return this.parentWidth  - this.width;  }
		  get yMax() { return this.parentHeight - this.height; }
		  
		  get xPos() { return this.transform.x; }
		  set xPos(value) { this.set({ x: value }); }

		  get yPos() { return this.transform.y; }
		  set yPos(value) { this.set({ y: value }); }
		  
		  get xPercent() { return this._xPercent; }
		  set xPercent(value) {
			this._xPercent = value;
			this.set({ left: value + "%", xPercent: -value });
		  }

		  get yPercent() { return this._yPercent; }
		  set yPercent(value) {
			this._yPercent = value;
			this.set({ top: value + "%", yPercent: -value });
		  }  
		  
		  // Creates an interceptor function for callbacks in the original vars object
		  createInterceptor(callbackName) {
			return () => {

			  var intercept = this["_" + callbackName];
			  var callback  = this._vars[callbackName];
			  var scope     = this._vars[callbackName + "Scope" ] || this;
			  var argsArray = this._vars[callbackName + "Params"] || [this.pointerEvent];

			  if (_.isFunction(intercept)) intercept.apply(this);

			  if (_.isFunction(callback)) callback.apply(scope, argsArray);
			};
		  }
		  
		  //obliczenie nowych pozycji dla mniejszego/większego okna/ekranu    
		  updateBounds() {
			this.parentWidth  = this.parent.offsetWidth;
			this.parentHeight = this.parent.offsetHeight;
		  }
		  
		  positionAbsolute(xPos, yPos, xPercent = 0, yPercent = 0) {

			this.xPos = xPos || this.xPercent * this.xMax / 100;
			this.yPos = yPos || this.yPercent * this.yMax / 100;
			this.xPercent = xPercent;
			this.yPercent = yPercent;
		  }

		  positionRelative(xPercent, yPercent, xPos = 0, yPos = 0) {
			
			this.xPercent = xPercent || this.xPos / this.xMax * 100;
			this.yPercent = yPercent || this.yPos / this.yMax * 100;
			this.xPos = xPos;
			this.yPos = yPos;
		  }
		  
		  set(vars) {
			TweenLite.set(this.target, vars);
		  }
		  

		  _onDragStart() {
			this.isActive = true;
		  }
		  
		  _onPress() {
			if (!this.isActive) {
			  this.positionAbsolute();
			  this.update();
			}
		  }
		  
		  _onRelease() {
			if (!this.isActive) {
			  this.isActive = false;
			  this.update(true);
			  this.positionRelative();
			}
		  }
			
		  _onThrowComplete() { //przeładuj stronę, po zakończeniu przesuwania elementu, obiektu
			this.isActive = false;
			this.update(true);
			this.positionRelative();
			location.reload();
		  }  
		}
	</script>
<!-- ******************************************************************* MENU ******************************************************************** -->
	<div class="container">
			<!-- wyświetlenie informacji o zalogowanym użytkowniku --> 
			<div class="header"> Zalogowany użytkownik: <span style="color:#F41127"><?php $login = $_SESSION['nick'];$typ=$_SESSION['typ_usera'];echo $login.' ('.$typ.')';?></span></div>
			<div class="menu" id="MENU"> <!-- pełna wersja menu dla admina -->
				<ol>
					<li class="active"> <a href="portal_admin.php"> Strona główna </a></li>
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
			
<!-- *************************************************** wyświetlenie aktualnego stanu wyjść systemu **********************************************-->
				
			<div id="tabela"> 
				<h2>Ostatni stan wejść/wyjść</h2>
				<table width="95%" align="center" border="1" bordercolor="#303030"  cellpadding="0" cellspacing="0"> 
				<tr>
				<?php
				
				//przeliczenie pierwotnie ustawionego czasu do alarmu drugiego stopnia na minuty i sekundy (pobrany czas jest w sekundach)
				$z = mysqli_query($connection, "SELECT czas_do_alarmu2 FROM ustawienia_systemu WHERE czujka='czujka_plomienia'");
				$w = mysqli_fetch_assoc($z);
				$czas = $w['czas_do_alarmu2'];
				$minuty = floor($czas / 60);
				$sekundy = $czas % 60;
				
				//pobranie ostatniego rekordu z bazy dotyczącego stanu systemu i wyświetlenie ich na ekranie
				$zapytanie= mysqli_query($connection,"SELECT * FROM stany_wyprowadzen ORDER BY id DESC LIMIT 1");
                $wynik = mysqli_fetch_assoc($zapytanie);
                if ($wynik>=1)
				{
						echo<<<END
						<td width="150" align="center" bgcolor="e5e5e5">Reset sterownika</td>
						<td width="150" align="center" bgcolor="e5e5e5">Potwierdzenie alarmu</td>
						<td width="150" align="center" bgcolor="e5e5e5">Czujnik płomienia</td>
						<td width="150" align="center" bgcolor="e5e5e5">Czujnik dymu</td>
						<td width="150" align="center" bgcolor="e5e5e5">ROP</td>
						<td width="150" align="center" bgcolor="e5e5e5">Czujnik temperatury</td>
						<td width="150" align="center" bgcolor="e5e5e5">Temperatura graniczna</td>
						<td width="150" align="center" bgcolor="e5e5e5">Alarm pierwszego stopnia</td>
						<td width="150" align="center" bgcolor="e5e5e5">Alarm drugiego stopnia</td>
						<td width="150" align="center" bgcolor="e5e5e5">Data i godzina ostatniego zdarzenia</td>
						<td width="150" align="center" bgcolor="e5e5e5">Ostatnia komunikacja sterownika z portalem</td>
						<td width="150" align="center" bgcolor="e5e5e5">Status</td>
						</tr><tr>
END;
					
					$RESET = $wynik['reset'];
					$POTWIERDZ = $wynik['potwierdz'];
					$CZUJKA_PLOMIENIA = $wynik['czujnik_plomienia'];
					$CZUJKA_DYMU = $wynik['czujnik_dymu'];
					$ROP = $wynik['rop'];
					$CZUJKA_TEMPERATURY = $wynik['czujnik_temperatury'];
					$aktualna_temperatura = $wynik['wartosc_temperatury'];
					$temperatura_graniczna = $wynik['temperatura_krytyczna'];
					$POZAR1 = $wynik['pozar1'];
					$POZAR2 = $wynik['pozar2'];
					$data = $wynik['datagodzina'];
					$data_ostatniego_laczenia = $wynik['data_ostatniego_laczenia'];
					$roznica = strtotime(date("Y-m-d H:i:s")) - strtotime($data_ostatniego_laczenia); //obliczona w sekundach różnica między aktualną datą, a datą ostatniego połączenia się sterownika z serwerem
						
					//odliczanie czasu od ostatniego połączenia się sterownika z serwerem i wyświetlanie tego czasu na ekranie (w formie dni, godzin, minut i sekund)
					$uptimer =  '<span id="countuptimer1"></span>

										<script>
											var czas = '.$roznica.';
											var dni = Math.floor(czas / 86400);
											var godziny = Math.floor((czas / 3600) % 24);
											var minuty = Math.floor((czas / 60) % 60);
											var sekundy = Math.floor(czas % 60);
											
												
											var downloadTimer = setInterval(function(){
											
											if (godziny >= 10 && godziny <= 23)
											{
												if (minuty >= 10 && minuty <= 59)
												{
													if (sekundy >= 10 && sekundy <= 59)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":"+minuty.toString()+":"+sekundy.toString();
													}
													else if (sekundy < 10 && sekundy >= 0)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":"+minuty.toString()+":0"+sekundy.toString();
													}
													else if (sekundy == 60)
													{
														minuty++;
														sekundy = 0;
														if (minuty < 60)
														{
															document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":"+minuty.toString()+":0"+sekundy.toString();
														}
														else if (minuty == 60)
														{
															document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":0"+minuty.toString()+":0"+sekundy.toString();
														}
													}
												}	
												else if (minuty < 10 && minuty >= 0)
												{
													if (sekundy >= 10 && sekundy <= 59)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":0"+minuty.toString()+":"+sekundy.toString();
													}
													else if (sekundy < 10 && sekundy >= 0)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":0"+minuty.toString()+":0"+sekundy.toString();
													}
													else if (sekundy == 60)
													{
														minuty++;
														sekundy = 0;
														if (minuty < 10)
														{
															document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":0"+minuty.toString()+":0"+sekundy.toString();
														}
														else if (minuty == 10)
														{
															document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":"+minuty.toString()+":0"+sekundy.toString();
														}
													}
												}
												if (minuty == 60)
												{
													godziny++;
													minuty = 0;
													sekundy = 0;
													if (godziny < 24)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":"+minuty.toString()+":0"+sekundy.toString();
													}
												}
											}
											else if (godziny < 10 && godziny >= 0)
											{
												if (minuty >= 10 && minuty <= 59)
												{
													if (sekundy >= 10 && sekundy <= 59)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni 0"+godziny.toString()+":"+minuty.toString()+":"+sekundy.toString();
													}
													else if (sekundy < 10 && sekundy >= 0)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni 0"+godziny.toString()+":"+minuty.toString()+":0"+sekundy.toString();
													}
													else if (sekundy == 60)
													{
														minuty++;
														sekundy = 0;
														if (minuty < 60)
														{
															document.getElementById("countuptimer1").textContent = dni.toString()+" dni 0"+godziny.toString()+":"+minuty.toString()+":0"+sekundy.toString();
														}
													}
												}	
												else if (minuty < 10 && minuty >= 0)
												{
													if (sekundy >= 10 && sekundy <= 59)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni 0"+godziny.toString()+":0"+minuty.toString()+":"+sekundy.toString();
													}
													else if (sekundy < 10 && sekundy >= 0)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni 0"+godziny.toString()+":0"+minuty.toString()+":0"+sekundy.toString();
													}
													else if (sekundy == 60)
													{
														minuty++;
														sekundy = 0;
														if (minuty < 10)
														{
															document.getElementById("countuptimer1").textContent = dni.toString()+" dni 0"+godziny.toString()+":0"+minuty.toString()+":0"+sekundy.toString();
														}
														else if (minuty == 10)
														{
															document.getElementById("countuptimer1").textContent = dni.toString()+" dni 0"+godziny.toString()+":"+minuty.toString()+":0"+sekundy.toString();
														}
													}
												}
												
												if (minuty == 60)
												{
													godziny++;
													minuty = 0;
													sekundy = 0;
													if (godziny < 10)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni 0"+godziny.toString()+":0"+minuty.toString()+":0"+sekundy.toString();
													}
													else if (godziny == 10)
													{
														document.getElementById("countuptimer1").textContent = dni.toString()+" dni "+godziny.toString()+":0"+minuty.toString()+":0"+sekundy.toString();
													}
												}
											}
											if (godziny == 24)
											{
												dni++;
												godziny = 0;
												minuty = 0;
												sekundy = 0;
												document.getElementById("countuptimer1").textContent = dni.toString()+" dni 0"+godziny.toString()+":0"+minuty.toString()+":0"+sekundy.toString();
											}

											sekundy++;
											},1000);
										</script>';	
					
					$liczba_uszkodzonych = 0;
					$liczba_alarmujacych = 0;
					
//******************************************************** ustawienie poszczególnych parametrów ******************************************************
					
					if ($RESET==1) //jeżeli system w stanie resetowania
					{
						//to odmierzaj i pokazuj czas do zresetowania
						$reset = '<p> Zresetowanie systemu nastąpi za:</p> 00:0<span id="countdowntimerreset"></span>

											<script type="text/javascript">
												var timeleft = 5;
												var Downloadtimer = setInterval(function(){
												document.getElementById("countdowntimerreset").textContent = timeleft;
												timeleft--;
												if(timeleft == 0)
												{   clearInterval(Downloadtimer);
												}
												},1000);
											</script>';
						
						//pokaż same "-" oprócz czujników, które są uszkodzone
						$potwierdz = '-';
						if ($CZUJKA_PLOMIENIA!="-")
						{
							$czujka_plomienia = '-';
						}
						else if ($CZUJKA_PLOMIENIA=="-")
						{
							$czujka_plomienia = "Czujnik płomienia uszkodzony";
						}
						if ($CZUJKA_DYMU!='-')
						{
							$czujka_dymu = '-';
						}
						else if ($CZUJKA_DYMU=='-')
						{
							$czujka_dymu = 'Czujnik dymu uszkodzony';
						}
						if ($ROP!='-')
						{
							$rop = '-';
						}
						else
						{
							$rop = 'ROP uszkodzony';
						}
						if ($CZUJKA_TEMPERATURY!='-')
						{
							$czujka_temperatury = '-';
						}
						else
						{
							$czujka_temperatury = 'Czujnik temperatury uszkodzony';
						}
						$pozar1='-';
						$pozar2='-';
					}
					else //jeżeli system nie jest w stanie resetowania
					{
						$reset='-';
						
						//jeżeli system jest w stanie potwierdzenia
						if ($POTWIERDZ==1)
						{
							//wyświetl odpowiednie przyciski i obrazki
							$potwierdz = '<form method="post">
													<input type="submit" style="font-size: 15px;
													width: auto;
													padding: 8px 5px;
													margin-top: 10px;
													letter-spacing: 0;"
													value="Kasuj stany czujek" name="Confirm1">
												</form>';
							$pozar1='<img src="\images/brak_alarmu.png" border="0" width="100" height="100">';
							$pozar2='<img src="\images/brak_alarmu.png" border="0" width="100" height="100">';
							$reset='<form method="post">
													<input type="submit" style="font-size: 15px;
													width: auto;
													padding: 8px 5px;
													margin-top: 10px;
													letter-spacing: 0;"
													value="Ustawienia początkowe systemu" name="Reset">
												</form>';
							if ($roznica<=70) //jeżeli system jest online, to odtwórz dźwięk
							{
								/*echo '<audio autoplay loop>
										<source src="\images/potwierdz.mp3">
									</audio>';*/
							}
						}
						else if ($POTWIERDZ==0) {$potwierdz='-';} //jeżeli system nie jest w stanie potwierdzenia, to pokaż "-" w miejscu, gdzie pokazywany jest stan potwierdzenia
						
						//niezależnie od potwierdzenia...
						
						//jeżeli konkretna czujka nie jest uszkodzona, to ustaw dla niej konkretny komunikat - w zależności od tego czy wskazuje alarm czy nie
						if ($CZUJKA_PLOMIENIA!="-")
						{
							if ($CZUJKA_PLOMIENIA==1){$czujka_plomienia='Wykryto płomień'; $liczba_alarmujacych++;}
							else {$czujka_plomienia='Nie wykryto płomienia';}
						}
						//jeżeli czujka jest uszkodzona, to wyświetl odpowiedni komunikat
						else if ($CZUJKA_PLOMIENIA=="-")
						{
							$czujka_plomienia = "Czujnik płomienia uszkodzony";
							$liczba_uszkodzonych++;
						}
						
						
						if ($CZUJKA_DYMU!="-")
						{
							if ($CZUJKA_DYMU==1){$czujka_dymu='Wykryto dym'; $liczba_alarmujacych++;}
							else {$czujka_dymu='Nie wykryto dymu';}
						}
						else if ($CZUJKA_DYMU=="-")
						{
							$czujka_dymu = "Czujnik dymu uszkodzony";
							$liczba_uszkodzonych++;

						}
						if ($ROP!="-")
						{
							if ($ROP==1){$rop='ROP został wciśnięty'; $liczba_alarmujacych++;}
							else {$rop='ROP nie został wciśnięty';}
						}
						else if ($ROP=="-")
						{
							$rop = 'ROP uszkodzony';
							$liczba_uszkodzonych++;
						}
						if ($CZUJKA_TEMPERATURY!="-")
						{
							if ($CZUJKA_TEMPERATURY==1){$czujka_temperatury='Wykryto zbyt wysoki przyrost temperatury'; $liczba_alarmujacych++;}
							else {$czujka_temperatury='Nie wykryto wysokiego przyrostu temperatury';}
						}
						else if ($CZUJKA_TEMPERATURY=="-")
						{
							$czujka_temperatury = 'Czujnik temperatury uszkodzony';
							$liczba_uszkodzonych++;
						}
					
						
						//w zależności od tego, ile czujek wskazuje alarm, a ile jest uszkodzonych, wyświetl odpowiednie obrazki w kolumnach informujących o pożarze
						if ($liczba_uszkodzonych<=3)
						{
							if ($liczba_alarmujacych==0)
							{
								$pozar1='<img src="\images/brak_alarmu.png" border="0" width="100" height="100">'; 
								$pozar2='<img src="\images/brak_alarmu.png" border="0" width="100" height="100">';
							}
							else if ($liczba_alarmujacych>=1)
							{
								if ($POZAR1==1)
								{
									$pozar1='<img src="\images/pozar.png" border="0" width="100" height="100">';  
									$pozar2='-';
								}
								else if ($POZAR2==1)
								{
									$pozar1='-'; 
									$pozar2='<img src="\images/pozar.png" border="0" width="100" height="100">'; 
								}
							}
						}
						else if ($liczba_uszkodzonych==4)
						{
							$potwierdz = '-';
							$reset = '-';
							$pozar1='-';
							$pozar2='-';
						}
					}
					
					//dodatkowe ustawienia
					//odtwórz odpowiedni komunikat w zależności od rodzaju alarmu oraz jeśli sterownik jest online
					if ($pozar1=='<img src="\images/pozar.png" border="0" width="100" height="100">' && $POTWIERDZ==0 && $roznica<=70)
					{
						/*echo '<audio autoplay loop>
								<source src="\images/1stopien.mp3" />
								</audio>';	*/
					}
					
					if ($pozar2=='<img src="\images/pozar.png" border="0" width="100" height="100">' && $POTWIERDZ==0 && $roznica<=70)
					{
						/*echo '<audio autoplay loop>
									<source src="\images/2stopien.mp3" />
								</audio>';*/
					}
						
					if ($liczba_alarmujacych>=1 && $POTWIERDZ==0 && $roznica <= 70) //jeśli system jest w stanie alarmowania i jest online, to pokaż odpowiednie przyciski
					{
						$potwierdz = '<form method="post">
												<input type="submit" 
												style="font-size: 15px;
												width: auto;
												padding: 8px 5px;
												margin-top: 10px;
												letter-spacing: 0;"
												value="Potwierdź alarm" name="Confirm">
											</form>';
						$reset='<form method="post">
												<input type="submit" style="font-size: 15px;
												width: auto;
												padding: 8px 5px;
												margin-top: 10px;
												letter-spacing: 0;"
												value="Ustawienia początkowe systemu" name="Reset">
											</form>';
						
						if ($pozar1!='-') //jeżeli jest czas alarmu pierwszego, a nie drugiego stopnia, to odliczaj czas do alarmu drugiego stopnia
						{
							$odliczanie_czasu_potwierdzenia = 'Zostało: <span id="countdowntimer"></span>
										<script>
											var Sekundy = '.$sekundy.';
											var Minuty = '.$minuty.';
											var DownloadTimer = setInterval(function(){
											
											
											if (Minuty >= 10) 
											{
												if (Sekundy >= 10) 
												{
													document.getElementById("countdowntimer").textContent = Minuty.toString()+":"+Sekundy.toString();
												}
												else if (Sekundy < 10 && Sekundy > 0) 
												{
													document.getElementById("countdowntimer").textContent = Minuty.toString()+":0"+Sekundy.toString();
												}
												else if (Sekundy == 0)
												{
													document.getElementById("countdowntimer").textContent = Minuty.toString()+":00";
													Minuty--;
													Sekundy = 60;
												}
											}
											else if (Minuty < 10 && Minuty >= 1) 
											{
												if (Sekundy >= 10) 
												{
													document.getElementById("countdowntimer").textContent = "0"+Minuty.toString()+":"+Sekundy.toString();
												}
												else if (Sekundy < 10 && Sekundy > 0) 
												{
													document.getElementById("countdowntimer").textContent = "0"+Minuty.toString()+":0"+Sekundy.toString();
												}
												else if (Sekundy == 0)
												{
													document.getElementById("countdowntimer").textContent = "0"+Minuty.toString()+":00";
													Minuty--;
													Sekundy = 60;
												}
											}
											else if (Minuty == 0) 
											{
												if (Sekundy >= 10) 
												{
													document.getElementById("countdowntimer").textContent = "00:"+Sekundy.toString();
												}
												else if (Sekundy < 10 && Sekundy > 0) 
												{
													document.getElementById("countdowntimer").textContent = "00:0"+Sekundy.toString();
												}
												else if (Sekundy == 0)
												{
													document.getElementById("countdowntimer").textContent = Minuty.toString()+":00";
													clearInterval(DownloadTimer);
												}
											}
											Sekundy--;
											},1000);
										</script>';
						}
					}
						
						
					if ($roznica <= 70) //jeżeli różnica (w sekundach) między aktualną datą, a datą ostatniego łączenia jest większa niż 70s, to wyświetl na stronie odpowiednią informację - ustaw sterownik w tryb OFFLINE
					{
						$status = '<span style="color:green;font-weight:bold"> ONLINE </span>';
						$czy_online = true;
					}
					else
					{
						$status = '<span style="color:red;font-weight:bold"> OFFLINE </span>';
						$czy_online = false;
					}
			
					//sprawdzanie, czy sterownik jest nadal w trybie ONLINE - dzięki AJAX-owi, w podanym skrypcie PHP jest na nowo obliczana różnica między tymi datami i wartość ta zwracana jest do tego skryptu
	
					echo '<script type="text/javascript">
								var roznica = 0;
								var czy_online = '.$czy_online.';
								var wlacz_funkcje = setInterval(function() {
										var xmlhttp = new XMLHttpRequest();
										xmlhttp.onreadystatechange = function() {
											if (this.readyState == 4 && this.status == 200) {
												roznica = this.responseText;
												if (roznica > 70 && czy_online)
												{
													location.href="portal_admin.php";
												}
											}
										};
										xmlhttp.open("GET", "czy_online.php?", true);
										xmlhttp.send();
									
								}, 1000);
							</script>';
						
						if (isset($_POST['Confirm']) && $roznica <= 70) //jeżeli potwierdzono alarm z poziomu strony i system jest ONLINE, to...
						{
							//pobierz ostatni rekord z bazy z informacjami o stanie czujników
							$pobierz = mysqli_query($connection, "SELECT * FROM stany_wyprowadzen ORDER BY id DESC LIMIT 1");
							$WYNIK = mysqli_fetch_assoc($pobierz);
							$flame = $WYNIK['czujnik_plomienia'];
							$smoke = $WYNIK['czujnik_dymu'];
							$przycisk = $WYNIK['rop'];
							$temperature = $WYNIK['czujnik_temperatury'];
							$value = $WYNIK['wartosc_temperatury'];
							$temp_kryt = $WYNIK['temperatura_krytyczna'];
							$czas_alarm2 = $WYNIK['czas_do_pozar2'];
							//i wstaw do bazy nowy rekord z tymi danymi, jednocześnie wyłączając alarmy i włączając potwierdzenie
							mysqli_query($connection, "INSERT INTO stany_wyprowadzen (czujnik_plomienia,czujnik_dymu,rop,czujnik_temperatury,wartosc_temperatury,temperatura_krytyczna,potwierdz,reset,pozar1,pozar2,czas_do_pozar2,data_ostatniego_laczenia) VALUES ('$flame','$smoke','$przycisk','$temperature','$value','$temp_kryt',1,0,0,0,'$czas_alarm2',NOW())"); 
						}
						
						if (isset($_POST['Confirm1']) && $roznica <= 70) //jeżeli wyłączono alarm z poziomu strony i system jest ONLINE, to...
						{
							//pobierz ostatni rekord z bazy z informacjami o stanie czujników
							$pobierz = mysqli_query($connection, "SELECT * FROM stany_wyprowadzen ORDER BY id DESC LIMIT 1");
							$WYNIK = mysqli_fetch_assoc($pobierz);
							//zresetuj czujniki, jeżeli nie wskazywały alarmu (nie są uszkodzone)
							$flame = $WYNIK['czujnik_plomienia'];
							if ($flame=='1')
							{
								$flame = '0';
							}
							$smoke = $WYNIK['czujnik_dymu'];
							if ($smoke=='1')
							{
								$smoke = '0';
							}
							$przycisk = $WYNIK['rop'];
							if ($przycisk=='1')
							{
								$przycisk = '0';
							}
							$temperature = $WYNIK['czujnik_temperatury'];
							if ($temperature=='1')
							{
								$temperature = '0';
							}
							$value = $WYNIK['wartosc_temperatury'];
							$temp_kryt = $WYNIK['temperatura_krytyczna'];
							$czas_alarm2 = $WYNIK['czas_do_pozar2'];
							//i wstaw do bazy nowy rekord z tymi danymi, jednocześnie wyłączając potwierdzenie
							mysqli_query($connection, "INSERT INTO stany_wyprowadzen (czujnik_plomienia,czujnik_dymu,rop,czujnik_temperatury,wartosc_temperatury,temperatura_krytyczna,potwierdz,reset,pozar1,pozar2,czas_do_pozar2,data_ostatniego_laczenia) VALUES ('$flame','$smoke','$przycisk','$temperature','$value','$temp_kryt',0,0,0,0,'$czas_alarm2',NOW())"); 
						}
						
						if (isset($_POST['Reset']) && $roznica <= 70) //jeżeli zresetowano system z poziomu strony i system jest ONLINE, to...
						{
							//pobierz ostatni rekord z bazy z informacjami o stanie czujników
							$pobierz = mysqli_query($connection, "SELECT * FROM stany_wyprowadzen ORDER BY id DESC LIMIT 1");
							$WYNIK = mysqli_fetch_assoc($pobierz);
							$flame = $WYNIK['czujnik_plomienia'];
							$smoke = $WYNIK['czujnik_dymu'];
							$przycisk = $WYNIK['rop'];
							$temperature = $WYNIK['czujnik_temperatury'];
							$value = $WYNIK['wartosc_temperatury'];
							$temp_kryt = $WYNIK['temperatura_krytyczna'];
							$czas_alarm2 = $WYNIK['czas_do_pozar2'];
							$confirm = $WYNIK['potwierdz'];
							$alarm1 = $WYNIK['pozar1'];
							$alarm2 = $WYNIK['pozar2'];
							//i wstaw do bazy nowy rekord z tymi danymi, jednocześnie włączając reset
							mysqli_query($connection, "INSERT INTO stany_wyprowadzen (czujnik_plomienia,czujnik_dymu,rop,czujnik_temperatury,wartosc_temperatury,temperatura_krytyczna,potwierdz,reset,pozar1,pozar2,czas_do_pozar2,data_ostatniego_laczenia) VALUES ('$flame','$smoke','$przycisk','$temperature','$value','$temp_kryt','$confirm',1,'$pozar1','$pozar2','$czas_alarm2',NOW())");
							//jeśli zresetowano system, to odblokuj monitorowanie czujników (zaznacz jako nieuszkodzone) i ustaw domyślne wartości temperatury granicznej i czasu do alarmu drugiego stopnia
							mysqli_query($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona=0");
							mysqli_query($connection, "UPDATE ustawienia_systemu SET czas_do_alarmu2=20 WHERE idcz=2");
							mysqli_query($connection, "UPDATE ustawienia_systemu SET temperatura_graniczna=70 WHERE idcz=1");
						}
						
						//wyświetl wszystkie ustawione parametry, dane
						echo<<<END
							<td width="150" align="center">$reset</td>
							<td width="150" align="center">$potwierdz $odliczanie_czasu_potwierdzenia</td>
							<td width="150" align="center">$czujka_plomienia</td>
							<td width="150" align="center">$czujka_dymu</td>
							<td width="150" align="center">$rop</td>
							<td width="150" align="center">$czujka_temperatury</td>
							<td width="150" align="center">$temperatura_graniczna&degC</td>
							<td width="150" align="center">$pozar1</td>
							<td width="150" align="center">$pozar2</td>
							<td width="150" align="center">$data</td>
							<td width="150" align="center">$data_ostatniego_laczenia <br> $uptimer</td>
							<td width="150" align="center">$status</td>
							</tr><tr>
END;
				}		
		?>
			
		<!-- *************************************** Wyświetlenie planu budynku i czujników na jego tle ************************************** -->
			
		</tr></table> <br /> <br />	
				</div>
				<div id="tytul"><h2> Plan budynku </h2></div>
				<div id="plan">
				
				<?php
					$zapytanie1 = mysqli_query($connection, "SELECT * FROM plan_budynku");
					$wynik1 = mysqli_fetch_assoc($zapytanie1);
					$plan = $wynik1['nazwa'];
					echo '<img src="\images/'.$plan.'" width="50%">'; //pobierz nazwę pliku z bazy danych, znajdź plik o podanej nazwie na serwerze i wyświetl go
					
					//pobierz dane o uszkodzonych czujnikach
					$SQL1 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_plomienia'");
					$WYNIK1 = mysqli_fetch_assoc($SQL1);
					$cz_p = $WYNIK1['czy_uszkodzona'];
		
					$SQL2 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_dymu'");
					$WYNIK2 = mysqli_fetch_assoc($SQL2);
					$cz_d = $WYNIK2['czy_uszkodzona'];
		
					$SQL3 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='rop'");
					$WYNIK3 = mysqli_fetch_assoc($SQL3);
					$r = $WYNIK3['czy_uszkodzona'];
		
					$SQL4 = mysqli_query ($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_temperatury'");
					$WYNIK4 = mysqli_fetch_assoc($SQL4);
					$cz_t = $WYNIK4['czy_uszkodzona'];
						
					if ($czy_online) //jeżeli system jest online, to pokaż czujniki w odpowiednich kolorach, z odpowiednimi komunikatami
					{
						if ($cz_p==1) 
						{
							echo '<style>#czujnik_plomienia{background: #ED931C;}</style><div id="czujnik_plomienia">Czujnik płomienia uszkodzony</div>';
						}
						else if ($czujka_plomienia=="Czujnik płomienia uszkodzony" && $cz_p==0)
						{
							echo '<style>#czujnik_plomienia{background: #18DE26;}</style><div id="czujnik_plomienia">Nie wykryto płomienia</div>';
						}
						else if ($czujka_plomienia=="Nie wykryto płomienia" || $czujka_plomienia=="-")
						{
							echo '<style>#czujnik_plomienia{background: #18DE26;}</style><div id="czujnik_plomienia">'.$czujka_plomienia.'</div>';
						}
						else if ($czujka_plomienia=="Wykryto płomień")
						{
							echo '<style>#czujnik_plomienia{background: #E62020;}</style><div id="czujnik_plomienia">'.$czujka_plomienia.'</div>';
						}
							
							
						if ($cz_d==1)
						{
							echo '<style>#czujnik_dymu{background: #ED931C;}</style><div id="czujnik_dymu">Czujnik dymu uszkodzony</div>';
						}
						else if ($czujka_dymu=="Czujnik dymu uszkodzony" && $cz_d==0)
						{
							echo '<style>#czujnik_dymu{background: #18DE26;}</style><div id="czujnik_dymu">Nie wykryto dymu</div>';
						}
						else if ($czujka_dymu=="Nie wykryto dymu" || $czujka_dymu=="-")
						{
							echo '<style>#czujnik_dymu{background: #18DE26;}</style><div id="czujnik_dymu">'.$czujka_dymu.'</div>';
						}
						else if ($czujka_dymu=="Wykryto dym")
						{
							echo '<style>#czujnik_dymu{background: #E62020;}</style><div id="czujnik_dymu">'.$czujka_dymu.'</div>';
						}
							
						if ($r==1)
						{
							echo '<style>#rop{background: #ED931C;}</style><div id="rop">ROP uszkodzony</div>';
						}
						else if ($rop=="ROP uszkodzony" && $r==0)
						{
							echo '<style>#rop{background: #18DE26;}</style><div id="rop">ROP nie został wciśnięty</div>';
						}
						else if ($rop=="ROP nie został wciśnięty" || $rop=="-")
						{
							echo '<style>#rop{background: #18DE26;}</style><div id="rop">'.$rop.'</div>';
						}
						else if ($rop=="ROP został wciśnięty")
						{
							echo '<style>#rop{background: #E62020;}</style><div id="rop">'.$rop.'</div>';
						}
							
						
						if ($cz_t==1)
						{
							echo '<style>#czujnik_temperatury{background: #ED931C;}</style><div id="czujnik_temperatury">Czujnik temperatury uszkodzony</div>';
						}
						else if ($czujka_temperatury=="Czujnik temperatury uszkodzony" && $cz_t==0)
						{
							echo '<style>#czujnik_temperatury{background: #18DE26;}</style><div id="czujnik_temperatury">Aktualna temperatura: <br/>'.$aktualna_temperatura.' &degC</div>';
						}
						else if ($czujka_temperatury=="Nie wykryto wysokiego przyrostu temperatury" || $czujka_temperatury=="-")
						{
							echo '<style>#czujnik_temperatury{background: #18DE26;}</style><div id="czujnik_temperatury">Aktualna temperatura: <br/>'.$aktualna_temperatura.' &degC</div>';
						}
						else if ($czujka_temperatury=="Wykryto zbyt wysoki przyrost temperatury")
						{
							echo '<style>#czujnik_temperatury{background: #E62020;}</style><div id="czujnik_temperatury">Zbyt wysoki przyrost/wysoka temp.: <br/>'.$aktualna_temperatura.' &degC</div>';
						}
					}
					else //jeżeli sterownik jest offline, to ustaw wszystkie czujniki na stronie w ten tryb
					{
						echo '<style>#czujnik_plomienia{background: #666666;}</style><div id="czujnik_plomienia">Sterownik offline</div>';
							
						echo '<style>#czujnik_dymu{background: #666666;}</style><div id="czujnik_dymu">Sterownik offline</div>';
						
						echo '<style>#rop{background: #666666;}</style><div id="rop">Sterownik offline</div>';
						
						echo '<style>#czujnik_temperatury{background: #666666;}</style><div id="czujnik_temperatury">Sterownik offline</div>';
		
						$zap = mysqli_query($connection, "SELECT * FROM ustawienia_systemu WHERE czujka='czujka_dymu'");
						$wyn = mysqli_fetch_assoc($zap);
						$czas = $wyn['czas_do_alarmu2'];
						
						//ustaw oryginalny czas do alarmu drugiego stopnia - dla pewności, że w razie wystąpienia alarmu drugiego stopnia, czas ten będzie odliczany i pokazywany prawidłowo
						mysqli_query($connection, "UPDATE ustawienia_systemu SET czas_do_alarmu2='$czas' WHERE czujka='czujka_plomienia'");
					}
					?>
				
				</div>
	</body>
</html>