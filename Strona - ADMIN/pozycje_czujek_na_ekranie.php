<?php 
		//pobierz dane bazy, z którą chcesz się połączyć
		require_once "connect.php";
		$connection = mysqli_connect($host, $user, $password);
		mysqli_query($connection, "SET CHARSET utf8");
		mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
		mysqli_select_db($connection, $db);
		
		//pobranie danych (dzięki AJAX-owi) o tym, której czujce zmieniono pozycję i pobranie tej pozycji
		$nowy1 = abs(round($_REQUEST['q1'], 1));
		$nowy2 = abs(round($_REQUEST['q2'], 1));
		$czujnik = $_REQUEST['q3'];
		
		//zaktualizuj dane dla odpowiedniego czujnika
		if ($czujnik=='czujnik_plomienia' && $nowy1 != 0 && $nowy2!= 0)
		{
			mysqli_query($connection, "UPDATE pozycje_czujek_na_ekranie SET od_gory='$nowy1',od_lewej='$nowy2' WHERE czujka='czujka plomienia'");
		}
		else if ($czujnik=='czujnik_dymu' && $nowy1 != 0 && $nowy2!= 0)
		{
			mysqli_query($connection, "UPDATE pozycje_czujek_na_ekranie SET od_gory='$nowy1',od_lewej='$nowy2' WHERE czujka='czujka dymu'");
			
		}
		else if ($czujnik=='rop' && $nowy1 != 0 && $nowy2!= 0)
		{
			mysqli_query($connection, "UPDATE pozycje_czujek_na_ekranie SET od_gory='$nowy1',od_lewej='$nowy2' WHERE czujka='rop'");
		}
		else if ($czujnik=='czujnik_temperatury' && $nowy1 != 0 && $nowy2!= 0)
		{
			mysqli_query($connection, "UPDATE pozycje_czujek_na_ekranie SET od_gory='$nowy1',od_lewej='$nowy2' WHERE czujka='czujka temperatury'");
		}
?>
