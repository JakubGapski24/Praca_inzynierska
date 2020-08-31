<?php

	session_start();
	
	session_unset(); //zakończ działanie sesji (zresetuj informację o zalogowaniu)
	
	header('Location: \index.php'); //wróć do panelu logowania

?>