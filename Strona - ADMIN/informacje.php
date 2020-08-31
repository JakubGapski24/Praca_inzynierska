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
					<li> <a href="ustawienia_systemu.php"> Ustawienia systemu </a></li>
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
			
			<!-- wyświetlenie informacji o istniejących elementach systemu -->	
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
					<td style="position:sticky;top:73px;" width="60" align="center" bgcolor="e5e5e5">Edycja</td>
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
				
					//ostatnia kolumna to przyciski wyboru, z których każdy jest unikatowy
					
					echo<<<END
					<td width="40" align="center">$i</td>
					<td width="40" align="center">$nazwa</td>
					<td width="60" align="center">$zdjecie</td>
					<td width="60" align="center">$opis</td>
					<td width="60" align="center"><input type="radio" id="radio$i" class="radio" name="edycja" value="$id" onclick="show()"><label for="radio$i" style="height:16px;"></label></td>
					</tr><tr>
END;
				}
			?>

			</tr></table>	
			
			</div>
			
			<div class="content" style="color:#000000;">
					
					<form method="post" ENCTYPE="multipart/form-data"> <!-- formularz zatwierdzania zmian w elementach systemu -->
						<input type="hidden" name="wybrany_element" id="element"> <!-- przypisz nazwę wybranego elementu do niewidocznej na ekranie zmiennej -->
						<select id="usun" name="usun" onchange="deactivate()"> <!-- pole wyboru czy usunąć dany element -->
							<option disabled="" selected>Czy usunąć wybrany element?</option>
							<option value="tak">Tak</option>
							<option value="nie">Nie</option>
						</select> <br>
						<!-- pole na nazwę elementu -->
						<input type="text" id="nazwa" name="nazwa" placeholder="Nazwa nowego elementu" onfocus="this.placeholder='Nazwa nowego elementu'" onblur="this.placeholder='Nazwa nowego elementu'"> <br> <br>
						<!-- pole na opis elementu -->
						<textarea placeholder="Opis nowego elementu..." id="editor" name="editor" style="width:30%;height:200px;"></textarea> <br>
						<!-- pole wstawiające zdjęcie elementu -->
						<input type="file" id="file" class="file" name="zdjecie" onchange="change()"><label for="file" id="label_file">Wstaw zdjęcie elementu</label><br>
						<input type="submit" name="button" value="Zapisz zmiany"> <!-- przycisk zatwierdzający zmiany -->
					</form>
					
					<?php
						if (isset($_POST['button'])) //jeżeli zatwierdzono zmiany
						{
							//pobierz ustawione w polach dane
							$wybrany_element = $_POST['wybrany_element'];
							$nazwa_elementu = $_POST['nazwa'];
							$opis_elementu = $_POST['editor'];
							$max_rozmiar = 5000000; //maksymalny dopuszczalny rozmiar wstawianego pliku w bajtach
							$zdjecie_elementu = $_FILES['zdjecie']['name'];
									
							if ($_POST['usun'] == "tak") //jeżeli usunąć wybrany element, to...
							{
								$zapytanie_o_zdjecie = mysqli_query($connection, "SELECT zdjecie FROM elementy_systemu WHERE ide='$wybrany_element'");
								$wynik1 = mysqli_fetch_assoc($zapytanie_o_zdjecie);
								$aktualne_zdjecie = $wynik1['zdjecie'];
								
								//usuń wybrany element z bazy i usuń plik (zdjęcie) z serwera
								$usun = mysqli_query($connection, "DELETE FROM elementy_systemu WHERE ide='$wybrany_element'");
								unlink("/images/$aktualne_zdjecie");
								
								if ($usun) //przeładuj stronę, jeśli usuwanie przebiegło pomyślnie i wyświetl odpowiedni komunikat
								{
									echo '<script>location.href="informacje.php";</script>'; //przeładuj stronę, gdy usuwanie elementu przebiegnie pomyślnie
								}
								else
								{
									echo '<br>';
									echo '<span style="color:red;">Coś poszło nie tak! Spróbuj jeszcze raz</span>';
									echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
								}
								
							}
							else //jeśli nie ma polecenia o usunięciu elementu, to...
							{
								if ($wybrany_element == '') //sprawdź, czy wybrano element do edycji
								{
									//wstawianie nowego elementu (nie wybrano nic do edycji)
									//następnie sprawdź, czy wymagane pola nie są puste
									if (!empty($_POST['nazwa']) && !empty($_POST['editor']) && is_uploaded_file($_FILES['zdjecie']['tmp_name']))
									{
										if ($_FILES['zdjecie']['size'] > $max_rozmiar) //sprawdzenie, czy wrzucany plik nie przekracza ustalonego rozmiaru
										{
											echo '<br>';
											echo '<span style="color:red;">Zbyt duży plik! Dopuszczalny rozmiar pliku to 5MB!</span>';
											echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
										}
										else if ($_FILES['zdjecie']['type'] != 'image/jpeg' && $_FILES['zdjecie']['type'] != 'image/jpg' && $_FILES['zdjecie']['type'] != 'image/png') //sprawdzenie, czy wrzucany plik ma odpowiedni format (jpg, jpeg lub png)
										{
											echo '<span style="color:red;">Zły format! Plik musi mieć format JPEG,JPG lub PNG!</span>';
											echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
										}
										else //jeśli wszystko w porządku, to...
										{
											move_uploaded_file($_FILES['zdjecie']['tmp_name'], '/images/'.$zdjecie_elementu); //wstaw nowy plik na serwer
										
											//i wstaw ustawione dane do bazy danych
											$wstaw = mysqli_query($connection, "INSERT INTO elementy_systemu (nazwa,zdjecie,opis) VALUES ('$nazwa_elementu','$zdjecie_elementu','$opis_elementu')");
										
											if ($wstaw && $_FILES['zdjecie']['error'] == 0)
											{
												echo '<script>location.href="informacje.php";</script>'; //przeładuj stronę, gdy wstawianie nowego elementu przebiegnie pomyślnie
											}
											else
											{
												echo '<span style="color:red;">Coś poszło nie tak!</span>';
											}
										}
									}
									else //jeśli któreś z pól puste, to wyświetl odpowiedni komunikat
									{
										if (empty($_POST['nazwa']))
										{
											echo '<br>';
											echo '<span style="color:red">Pole z nazwą jest puste!</span>';
											echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
										}
								
										if (empty($_POST['editor']))
										{
											echo '<br>';
											echo '<span style="color:red">Pole z opisem jest puste!</span>';
											echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
										}
							
										if (!(is_uploaded_file($_FILES['zdjecie']['tmp_name'])))
										{
											echo '<br>';
											echo '<span style="color:red">Nie wstawiono zdjęcia!</span>';
											echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
										}
									}
								}
								else //wybrano element do edycji
								{
									//sprawdz czy przynajmniej jedno z pól nie jest puste
									if (!empty($_POST['nazwa']) || !empty($_POST['editor']) || is_uploaded_file($_FILES['zdjecie']['tmp_name']))
									{

										if (is_uploaded_file($_FILES['zdjecie']['tmp_name'])) //jeżeli jest zmieniane zdjęcie, to...
										{
											if ($_FILES['zdjecie']['size'] > $max_rozmiar) //sprawdzenie, czy wrzucany plik nie przekracza ustalonego rozmiaru
											{
												echo '<br>';
												echo '<span style="color:red;">Zbyt duży plik! Dopuszczalny rozmiar pliku to 5MB!</span>';
												echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
											}
											else if ($_FILES['zdjecie']['type'] != 'image/jpeg' && $_FILES['zdjecie']['type'] != 'image/jpg' && $_FILES['zdjecie']['type'] != 'image/png') //sprawdzenie, czy wrzucany plik ma odpowiedni format (jpg, jpeg lub png)
											{
												echo '<span style="color:red;">Zły format! Plik musi mieć format JPEG,JPG lub PNG!</span>';
												echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
											}
											else //jeśli wrzucany plik jest ok, to...
											{
												$zapytanie_o_zdjecie = mysqli_query($connection, "SELECT zdjecie FROM elementy_systemu WHERE ide='$wybrany_element'");
												$wynik1 = mysqli_fetch_assoc($zapytanie_o_zdjecie);
												$aktualne_zdjecie = $wynik1['zdjecie'];
												
												unlink("/images/$aktualne_zdjecie"); //usuń z serwera poprzednie zdjęcie edytowanego elementu
												move_uploaded_file($_FILES['zdjecie']['tmp_name'], '/images/'.$zdjecie_elementu); //wrzuć na serwer obecnie wstawiane zdjęcie
												
												mysqli_query($connection, "UPDATE elementy_systemu SET zdjecie='$zdjecie_elementu' WHERE ide='$wybrany_element'"); //zapisz w bazie danych odpowiednią informację o nazwie zdjęcia edytowanego elementu
											}
										}
										
										if (!empty($_POST['nazwa'])) //jeśli pole z nazwą elementu nie jest puste, to wstaw tą nazwę do bazy danych
										{
											mysqli_query($connection, "UPDATE elementy_systemu SET nazwa='$nazwa_elementu' WHERE ide='$wybrany_element'");
										}
								
										if (!empty($_POST['editor'])) //jeśli pole z opisem elementu nie jest puste, to wstaw ten opis do bazy danych
										{
											mysqli_query($connection, "UPDATE elementy_systemu SET opis='$opis_elementu' WHERE ide='$wybrany_element'"); 
										}
										
										//przeładuj stronę po poprawnej edycji danych
										echo '<script>location.href="informacje.php";</script>';
									}
									else //jeśli wszystkie pola potrzebne do edycji elementu puste, to wyświetl odpowiedni komunikat
									{
										echo '<br>';
										echo '<span style="color:red">Wszystkie pola są puste! Przynajmniej jedno pole musi mieć zawartość!</span>';
										echo '<script>window.scrollTo(0, document.documentElement.scrollHeight);</script>';
									}	
								}
							}
						}
					?>
			</div>
		
			<script>
			
					document.getElementById("usun").disabled = true; //domyślnie pole z wyborem czy usunąć element jest wyłączone - aktywuje się dopiero po wyborze elementu do edycji
					
					
					function show() //funkcja, dzięki której możliwe jest pobranie danych dotyczących wybranego do edycji elementu i wpisanie ich w odpowiednie pola do edycji
					{
						window.scrollTo(0, document.documentElement.scrollHeight); //przesuń stronę na sam dół
						var info = document.querySelector('input[name="edycja"]:checked').value; //pobierz numer w bazie wybranego elementu
						document.getElementById("element").value = info; //przypisz nazwę elementu do zmiennej, niewidocznej z poziomu strony, a która umożliwi edycję wybranego elementu
						var pobrane_info = '';
						document.getElementById("usun").disabled = false; //aktywuj pole z wyborem, czy usunąć wybrany element

						var xmlhttp = new XMLHttpRequest();
						
						xmlhttp.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200) 
							{
								pobrane_info = this.responseText; //pobranie, dzięki AJAX-owi, nazwy wybranego elementu
								document.getElementById("nazwa").value = pobrane_info; //wpisanie pobranej nazwy w pole do edycji nazwy
							}
						};
						xmlhttp.open("GET", "pobierz_info.php?info=" + info + "&ile=" + 1, true); //wysłanie informacji o tym, żeby pobrać nazwę elementu
						xmlhttp.send();
						
						var xmlhttp = new XMLHttpRequest();
						
						xmlhttp.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200) 
							{
								pobrane_info = this.responseText; //pobranie, dzięki AJAX-owi, opisu wybranego elementu
								document.getElementById("editor").value = pobrane_info; //wpisanie pobranego opisu w pole do edycji opisu
							}
						};
						xmlhttp.open("GET", "pobierz_info.php?info=" + info + "&ile=" + 2, true); //wysłanie informacji o tym, żeby pobrać opis elementu
						xmlhttp.send();
					}
					
					
					function deactivate() //funkcja wyłączająca pola, gdy ustawiona jest opcja "usuń wybrany element"
					{
						if (document.getElementById("usun").value == 'tak') //jeśli opcja decydująca o usunięciu elementu jest ustawiona na "tak", to dezaktywuj pozostałe pola do edycji
						{
							document.getElementById("nazwa").disabled = true;
							document.getElementById("editor").disabled = true;
							document.getElementById("file").disabled = true;
						}
						else //w przeciwnym wypadku, z powrotem je aktywuj
						{
							document.getElementById("nazwa").disabled = false;
							document.getElementById("editor").disabled = false;
							document.getElementById("file").disabled = false;
						}
					}
					
					function change() //funkcja, dzięki której możliwe jest zobaczenie nazwy wybranego pliku
					{
						var napis = document.getElementById("file").value;
						if (napis == '') //jeśli nie wybrano pliku pokaż polecenie "wybierz plik"
						{
							document.getElementById("label_file").innerHTML = 'Wstaw zdjęcie elementu';
						}
						else //w przeciwnym wypadku wyświetl nazwę pliku (bez ścieżki, samą nazwę)
						{
							var bez_sciezki = napis.split(/(\\|\/)/g).pop();
							document.getElementById("label_file").innerHTML = bez_sciezki;
						}
					}
					
					</script>
	</div>
	</body>	