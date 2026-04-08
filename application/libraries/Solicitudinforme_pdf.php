<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(FCPATH . '/application/libraries/tcpdf/tcpdf.php');

class SolicitudInformePdfDocument extends TCPDF {
	public function Header(){
		$this->SetFont('helvetica', 'B', 12);
		$this->SetTextColor(33, 37, 41);
		$this->Cell(0, 8, '2OP - Informe Medico', 0, 1, 'L');
		$this->SetDrawColor(180, 180, 180);
		$this->Line(10, 18, 200, 18);
	}

	public function Footer(){
		$this->SetY(-15);
		$this->SetFont('helvetica', '', 8);
		$this->SetTextColor(120, 120, 120);
		$this->Cell(0, 10, 'Pagina ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
	}
}

class Solicitudinforme_pdf {

	private function esc($value){
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	private function row($label, $value){
		$label = $this->esc($label);
		$value = $this->esc($value === '' ? '-' : $value);
		return '<tr><td style="width:32%;background-color:#f7f7f7;border:1px solid #dddddd;padding:6px;"><strong>' . $label . '</strong></td><td style="width:68%;border:1px solid #dddddd;padding:6px;">' . $value . '</td></tr>';
	}

	public function outputInformeSolicitud($payload){
		$pdf = new SolicitudInformePdfDocument(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator('2OP');
		$pdf->SetAuthor('2OP');
		$pdf->SetTitle('Informe solicitud');
		$pdf->SetMargins(10, 24, 10);
		$pdf->SetHeaderMargin(8);
		$pdf->SetFooterMargin(12);
		$pdf->SetAutoPageBreak(true, 18);
		$pdf->AddPage();

		$pdf->SetFont('helvetica', 'B', 14);
		$pdf->Cell(0, 8, 'Informe de Solicitud', 0, 1, 'L');
		$pdf->Ln(1);

		$pdf->SetFont('helvetica', '', 10);
		$htmlSolicitud = '';
		$htmlSolicitud .= '<h3 style="font-size:12px;color:#333333;">Datos de solicitud</h3>';
		$htmlSolicitud .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		$htmlSolicitud .= $this->row('ID solicitud', isset($payload['solicitud_id']) ? $payload['solicitud_id'] : '');
		$htmlSolicitud .= $this->row('Codigo cliente', isset($payload['solicitud_codigo']) ? $payload['solicitud_codigo'] : '');
		$htmlSolicitud .= $this->row('Nombre solicitud', isset($payload['nombre_solicitud']) ? $payload['nombre_solicitud'] : '');
		$htmlSolicitud .= $this->row('Estado', isset($payload['estado']) ? $payload['estado'] : '');
		$htmlSolicitud .= $this->row('Origen', isset($payload['origen']) ? $payload['origen'] : '');
		$htmlSolicitud .= $this->row('Fecha creacion', isset($payload['fecha_creacion']) ? $payload['fecha_creacion'] : '');
		$htmlSolicitud .= '</table>';
		$pdf->writeHTML($htmlSolicitud, true, false, true, false, '');

		$pdf->Ln(2);
		$htmlPaciente = '';
		$htmlPaciente .= '<h3 style="font-size:12px;color:#333333;">Datos de paciente y contacto</h3>';
		$htmlPaciente .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		$htmlPaciente .= $this->row('Paciente', isset($payload['paciente']) ? $payload['paciente'] : '');
		$htmlPaciente .= $this->row('Documento', isset($payload['paciente_documento']) ? $payload['paciente_documento'] : '');
		$htmlPaciente .= $this->row('Fecha nacimiento', isset($payload['paciente_fecha_nacimiento']) ? $payload['paciente_fecha_nacimiento'] : '');
		$htmlPaciente .= $this->row('Sexo', isset($payload['paciente_sexo']) ? $payload['paciente_sexo'] : '');
		$htmlPaciente .= $this->row('Email', isset($payload['email']) ? $payload['email'] : '');
		$htmlPaciente .= $this->row('Telefono', isset($payload['telefono']) ? $payload['telefono'] : '');
		$htmlPaciente .= '</table>';
		$pdf->writeHTML($htmlPaciente, true, false, true, false, '');

		$pdf->Ln(2);
		$htmlInforme = '';
		$htmlInforme .= '<h3 style="font-size:12px;color:#333333;">Datos del informe PACS</h3>';
		$htmlInforme .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		$htmlInforme .= $this->row('Fecha informe', isset($payload['informe_fecha']) ? $payload['informe_fecha'] : '');
		$htmlInforme .= $this->row('Radiologo', isset($payload['radiologo']) ? $payload['radiologo'] : '');
		$htmlInforme .= $this->row('Colegiado', isset($payload['colegiado']) ? $payload['colegiado'] : '');
		$htmlInforme .= $this->row('Patient ID', isset($payload['patient_id']) ? $payload['patient_id'] : '');
		$htmlInforme .= '</table>';
		$pdf->writeHTML($htmlInforme, true, false, true, false, '');

		$pdf->Ln(2);
		$pdf->SetFont('helvetica', 'B', 11);
		$pdf->Cell(0, 7, 'Informe medico', 0, 1, 'L');
		$pdf->SetFont('helvetica', '', 10);
		$report = isset($payload['informe_texto']) && trim((string)$payload['informe_texto']) !== '' ? (string)$payload['informe_texto'] : 'Sin contenido de informe.';
		$pdf->MultiCell(0, 0, $report, 1, 'L', false, 1, '', '', true, 0, true);

		$fileName = 'informe_solicitud_' . (isset($payload['solicitud_id']) ? (int)$payload['solicitud_id'] : 0) . '.pdf';
		$pdf->Output($fileName, 'I');
	}
}
