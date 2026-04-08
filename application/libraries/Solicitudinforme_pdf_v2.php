<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(FCPATH . '/application/libraries/tcpdf/tcpdf.php');

class SolicitudInformePdfV2Document extends TCPDF {
	public $headerLogoPath = '';
	public $headerContactLines = array();

	public function Header(){
		$this->SetFillColor(246, 246, 246);
		$this->Rect(0, 0, 210, 297, 'F');

		if($this->headerLogoPath !== '' && file_exists($this->headerLogoPath)){
			$this->Image($this->headerLogoPath, 12, 9, 72, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
		}

		$this->SetFont('helvetica', '', 10.5);
		$this->SetTextColor(35, 35, 35);
		$y = 10;
		foreach($this->headerContactLines as $line){
			$this->SetXY(165, $y);
			$this->Cell(34, 5.2, (string)$line, 0, 1, 'L');
			$y += 5.1;
		}

		$this->SetDrawColor(140, 140, 140);
		$this->SetLineWidth(0.35);
		$this->Line(20, 48.0, 200, 48.0);

		$this->SetFont('helvetica', 'B', 16);
		$this->SetTextColor(65, 92, 126);
		$this->SetXY(20, 40);
		$this->Cell(180, 6, 'INFORME DE RADIODIAGNOSTICO', 0, 1, 'C');
	}

	public function Footer(){
	}
}

class Solicitudinforme_pdf_v2 {

	private function val($payload, $key, $default = '-'){
		if(!isset($payload[$key])){
			return $default;
		}
		$value = trim((string)$payload[$key]);
		return $value === '' ? $default : $value;
	}

	private function formatSpanishDate($rawDate){
		$rawDate = trim((string)$rawDate);
		if($rawDate === ''){
			return '-';
		}

		$timestamp = strtotime($rawDate);
		if($timestamp === false){
			return $rawDate;
		}

		$dias = array('domingo','lunes','martes','miercoles','jueves','viernes','sabado');
		$meses = array(1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre');
		return $dias[(int)date('w', $timestamp)] . ', ' . (int)date('j', $timestamp) . ' de ' . $meses[(int)date('n', $timestamp)] . ' de ' . (int)date('Y', $timestamp);
	}

	private function writeSectionTitle($pdf, $title){
		$pdf->SetFont('helvetica', 'BI', 15);
		$pdf->SetTextColor(65, 92, 126);
		$pdf->Cell(0, 8, strtoupper((string)$title), 0, 1, 'L');
		$pdf->SetTextColor(25, 25, 25);
		$pdf->SetFont('helvetica', '', 11.8);
	}

	private function writeTopInfoTable($pdf, $payload){
		$rows = array(
			array('Nombre y Apellidos', $this->val($payload, 'paciente')),
			// array('Tipo de Estudio', $this->val($payload, 'tipo_estudio', $this->val($payload, 'nombre_solicitud'))),
			// array('Zona de Exploracion', $this->val($payload, 'zona_exploracion', $this->val($payload, 'tipo_estudio', '-'))),
			// array('Fecha del Estudio', $this->formatSpanishDate($this->val($payload, 'fecha_estudio', ''))),
			array('Fecha del Informe', $this->formatSpanishDate($this->val($payload, 'informe_fecha', ''))),
			// array('Medico Prescriptor', $this->val($payload, 'medico_prescriptor', '-')),
			// array('Sociedad Medica', $this->val($payload, 'sociedad_medica', 'ASISA')),
			array('Numero de identificacion del estudio', $this->val($payload, 'identificador_estudio', $this->val($payload, 'patient_id', '-'))),
			// array('Tecnico de Radiodiagnostico', $this->val($payload, 'tecnico_radiodiagnostico', '-')),
		);

		$startY = 48.5;
		$rowH = 6.5;
		$pdf->SetFillColor(246, 246, 246);
		$pdf->Rect(10, $startY, 12, ($rowH * count($rows)) + 2, 'F');

		$pdf->SetFont('helvetica', 'B', 10.8);
		$pdf->SetTextColor(30, 30, 30);
		$y = $startY;
		foreach($rows as $row){
			$pdf->SetXY(20, $y);
			$pdf->Cell(72, $rowH, (string)$row[0], 0, 0, 'L');
			$pdf->SetFont('helvetica', '', 10.8);
			$pdf->SetXY(95, $y);
			$pdf->Cell(102, $rowH, (string)$row[1], 0, 1, 'L');
			$pdf->SetFont('helvetica', 'B', 10.8);
			$y += $rowH;
		}

		$pdf->SetDrawColor(140, 140, 140);
		$pdf->SetLineWidth(0.35);
		$pdf->Line(20, $y + 0.7, 200, $y + 0.7);
		$pdf->SetY($y + 4);
	}

	private function writeBodyText($pdf, $text){
		$text = trim((string)$text);
		if($text === ''){
			$text = '-';
		}
		$pdf->MultiCell(0, 7, $text, 0, 'L', false, 1);
	}

	private function drawWatermark($pdf){
		$watermarkPath = FCPATH . 'image/logo-segunda-opinion-radiologica.png';
		if(file_exists($watermarkPath)){
			$pdf->SetAlpha(0.08);
			$pdf->StartTransform();
			$pdf->Rotate(45, 105, 160);
			// Cabecera: ancho 72mm. Marca de agua al 200% => 144mm.
			$pdf->Image($watermarkPath, 33, 124, 144, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
			$pdf->StopTransform();
			$pdf->SetAlpha(1);
		}
	}

	private function drawSignatureArea($pdf, $payload){
		$pdf->SetY(242);
		$pdf->SetFont('helvetica', 'B', 11);
		$pdf->SetTextColor(25, 25, 25);
		$pdf->SetXY(20, 262);
		$pdf->Cell(110, 6.5, 'Facultativo Especialista en Radiodiagnostico', 0, 1, 'L');
		$pdf->SetXY(20, 268.2);
		$pdf->Cell(110, 6.5, 'N° de Colegiado', 0, 1, 'L');

		$pdf->SetFont('helvetica', '', 11.5);
		$pdf->SetXY(118, 264.5);
		$pdf->MultiCell(82, 7, strtoupper($this->val($payload, 'radiologo', '-')) . "\n" . $this->val($payload, 'colegiado', '-'), 0, 'R', false, 1);
		$pdf->SetDrawColor(30, 30, 30);
		$pdf->SetLineWidth(0.5);
		$pdf->Line(140, 253, 194, 253);
	}

	public function outputInformeSolicitud($payload){
		$pdf = new SolicitudInformePdfV2Document(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->headerLogoPath = FCPATH . 'image/logo-segunda-opinion-radiologica.png';
		$pdf->headerContactLines = array(
			'+34 91 683 94 50',
			'+34 654 900 300',
			'C/ Alvaro de Bazan 15',
			'28902 Getafe (Madrid)',
			'info@magnetosur.com',
			'www.magnetosur.com',
		);

		$pdf->SetCreator('2OP');
		$pdf->SetAuthor('2OP');
		$pdf->SetTitle('Informe de Radiodiagnostico');
		$pdf->SetMargins(20, 45, 10);
		$pdf->SetAutoPageBreak(true, 18);
		$pdf->AddPage();

		$this->drawWatermark($pdf);
		$this->writeTopInfoTable($pdf, $payload);

		$this->writeSectionTitle($pdf, 'Informe');
		$this->writeBodyText($pdf, $this->val($payload, 'informe_texto', '-'));

		$this->drawSignatureArea($pdf, $payload);
		$pdf->Output('informe_solicitud_' . (int)$this->val($payload, 'solicitud_id', 0) . '.pdf', 'I');
	}
}
