<?php
session_start();
require('TCPDF-master/tcpdf.php'); //wywołaj plik umożliwiający tworzenie plików PDF

$dane = $_SESSION['zdarzenia']; //ustaw dane do wstawienia do pliku
$data_generacji = date("Y-m-d H:i:s");
$dane .= '<br><span style="font-size:10px;"> Wygenerowano: '.$data_generacji.'</span>'; //do otrzymanych danych dodaj informację o dacie wygenerowania pliku PDF

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); //stwórz nowy plik PDF

if (@file_exists(dirname(__FILE__).'/lang/pol.php')) {
        require_once(dirname(__FILE__).'/lang/pol.php');
        $pdf->setLanguageArray($l);
}
$pdf->setPrintHeader(false); //usunięcie stopki i nagłówka strony header/footer
$pdf->setPrintFooter(false);
$pdf->SetFont('dejavusans', '', 8); //polskie znaki - dejavusans lub freesans
$pdf->AddPage(); //dodaj stronę, gdy poprzednia się skończy
$pdf->writeHTML($dane, true, false, true, false, ''); //wstaw dane do pliku
$pdf->Output('Zdarzenia '.$data_generacji.' .pdf', 'I'); //ustaw nazwę pliku na podaną z uwzględnieniem daty generacji pliku
?>