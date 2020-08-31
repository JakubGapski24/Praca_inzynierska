<?php
	//pobierz dane bazy, z którą chcesz się połączyć
	require_once "connect.php";
	$connection = mysqli_connect($host, $user, $password);
	mysqli_query($connection, "SET CHARSET utf8");
	mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
	mysqli_select_db($connection, $db); 
	
	//pobranie danych przesłanych ze sterownika
	$czujnik_plomienia = $_GET['czujnik_plomienia'];
	$czujnik_dymu = $_GET['czujnik_dymu'];
	$rop = $_GET['rop'];
	$czujnik_temp = $_GET['czujnik_temperatury'];
	$aktualna_temp = $_GET['aktualna_temp'];
	$potwierdz = $_GET['potwierdz'];
	$reset = $_GET['reset'];
	$pozar1 = $_GET['pozar1'];
	$pozar2 = $_GET['pozar2'];
	$czas = $_GET['czas_do_alarmu2'];
	$is60s_or_increasing_temperature = $_GET['is60s_or_increasing_temperature'];
	
	//pobranie aktualnej temperatury granicznej, w celu umieszczenia prawidłowej i aktualnej wartości w bazie wraz z nowym rekordem/aktualizacją
	$zapytanie_o_aktualna_temperature_graniczna = mysqli_query($connection, "SELECT temperatura_graniczna FROM ustawienia_systemu WHERE czujka='czujka_plomienia'");
	$wynik = mysqli_fetch_assoc($zapytanie_o_aktualna_temperature_graniczna);
	$temperatura_graniczna = $wynik['temperatura_graniczna'];
	
	//pobranie oryginalnej wartości czasu do alarmu drugiego stopnia, w celu umieszczenia prawidłowej i aktualnej wartości w bazie wraz z nowym rekordem/aktualizacją
	$zapytanie_o_czas_do_alarmu2 = mysqli_query($connection, "SELECT czas_do_alarmu2 FROM ustawienia_systemu WHERE czujka='czujka_dymu'");
	$wynik = mysqli_fetch_assoc($zapytanie_o_czas_do_alarmu2);
	$czas_do_alarmu2 = $wynik['czas_do_alarmu2'];
	
	
	//jeżeli ta zmienna jest ustawiona na 'false', to znaczy, że nastąpiła zmiana stanu systemu i należy wprowadzić nowy rekord do bazy danych
	if (!$is60s_or_increasing_temperature)
	{
		mysqli_query($connection, "INSERT INTO stany_wyprowadzen (czujnik_plomienia,czujnik_dymu,rop,czujnik_temperatury,wartosc_temperatury,temperatura_krytyczna,potwierdz,reset,pozar1,pozar2,czas_do_pozar2,data_ostatniego_laczenia) VALUES ('$czujnik_plomienia','$czujnik_dymu','$rop','$czujnik_temp','$aktualna_temp','$temperatura_graniczna','$potwierdz','$reset','$pozar1','$pozar2','$czas_do_alarmu2',NOW())");
		
		if ($reset) //jeśli zresetowano system, to odblokuj monitorowanie czujników (zaznacz jako nieuszkodzone) i ustaw domyślne wartości temperatury granicznej i czasu do alarmu drugiego stopnia
		{
			mysqli_query($connection, "UPDATE ustawienia_systemu SET czy_uszkodzona=0");
			mysqli_query($connection, "UPDATE ustawienia_systemu SET czas_do_alarmu2=20 WHERE idcz=2");
			mysqli_query($connection, "UPDATE ustawienia_systemu SET temperatura_graniczna=70 WHERE idcz=1");
		}
	}
	//w przeciwnym wypadku zaktualizuj ostatni rekord w bazie o aktualną, zmierzoną temperaturę otoczenia; wartość temperatury granicznej; ustawiony czas do alarmu drugiego stopnia oraz o aktualną datę ostatniego połączenia sterownika z serwerem
	else if ($is60s_or_increasing_temperature)
	{
		mysqli_query($connection, "UPDATE stany_wyprowadzen SET wartosc_temperatury='$aktualna_temp',temperatura_krytyczna='$temperatura_graniczna',czas_do_pozar2='$czas_do_alarmu2', data_ostatniego_laczenia=NOW() ORDER BY id DESC LIMIT 1");
	}
	
	//gdy system w stanie alarmowania pierwszego stopnia, to aktualizuj ten czas w bazie, aby móc poprawnie wyświetlać na stronie pozostały czas, gdyby kolejna z czujek wskazała alarm
	if ($czas!=0) 
	{
		mysqli_query($connection, "UPDATE ustawienia_systemu SET czas_do_alarmu2='$czas' WHERE idcz=1");
	}
	
	//jeżeli system nie wskazuje już alarmu pierwszego stopnia i ustawiony czas w bazie nie jest pierwotnie ustawionym czasem do alarmu drugiego stopnia, to ustaw ten 'błędny' czas na pierwotną wartość
	if (!$pozar1 && $czas!=$czas_do_alarmu2)
	{
		mysqli_query($connection, "UPDATE ustawienia_systemu SET czas_do_alarmu2='$czas_do_alarmu2' WHERE idcz=1");
	}
	 
?>