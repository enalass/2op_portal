<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class GeneraExcel
{
	protected $ci;

	public function __construct()
	{
	        $this->ci =& get_instance();

	        $this->ci->load->library('PHPExcel');
	        $this->ci->load->model('applicationsmodel');

	}

	public function genera($search='',$type='All'){

		$applications = $this->ci->applicationsmodel->getApplicationsExcel($search,$type);

		return $this->generaExcel($applications);
		
	}

	private function generaExcel($applications){

		date_default_timezone_set('Europe/Madrid');
		ini_set('memory_limit', '1024M');
        
		//Cogemos los datos

       	// $this->ci->load->library('PHPExcel');
        // Propiedades del archivo excel
        $this->ci->phpexcel->getProperties()
                ->setTitle("List of applications")
                ->setDescription("List of applications");
        // Setiar la solapa que queda actia al abrir el excel
        $this->ci->phpexcel->setActiveSheetIndex(0);
        $sheet = $this->ci->phpexcel->getActiveSheet();
        $sheet->setTitle("Applications");

        if( $applications != false){
        	$sheet->setCellValue('A1', 'Date');
        	$sheet->setCellValue('B1', 'Applicant Name');
        	$sheet->setCellValue('C1', 'Application for');
        	$sheet->setCellValue('D1', 'Country');
        	$sheet->setCellValue('E1', 'Region');
        	$sheet->setCellValue('F1', 'Status');
        	$sheet->setCellValue('G1', 'Nomination status');
        	$sheet->setCellValue('H1', 'Organization type');


        	$fila = 2;
        	foreach ($applications->result() as $application) {
        		$col = 0;
        		$sheet->setCellValueByColumnAndRow($col++, $fila ,$application->APP_DT_CREATE);
        		$sheet->setCellValueByColumnAndRow($col++, $fila ,$application->APP_DS_NAME . " " . $application->APP_DS_SURNAME);
        		$sheet->setCellValueByColumnAndRow($col++, $fila ,$application->POS_DS_NAME);
        		$sheet->setCellValueByColumnAndRow($col++, $fila ,$application->APP_DS_COUNTRY);
        		$sheet->setCellValueByColumnAndRow($col++, $fila ,$application->CTR_DS_REGION);
        		$sheet->setCellValueByColumnAndRow($col++, $fila ,$application->SNO_DS_NAME);
        		$sheet->setCellValueByColumnAndRow($col++, $fila ,$application->NST_DS_NAME);
        		$sheet->setCellValueByColumnAndRow($col++, $fila ,$application->APP_DS_NOMINATIONFROM);
        		
        		$fila++;
        	}
        }else{
        	$sheet->setCellValue('A1', 'No applications to show');
        }

        


        return $sheet;
	}
	

}

/* End of file GeneraExcel.php */
/* Location: ./application/libraries/GeneraExcel.php */
