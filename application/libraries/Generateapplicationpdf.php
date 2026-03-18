<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once(FCPATH.'/application/libraries/tcpdf/tcpdf.php');

class MYPDF extends TCPDF {

	//Page header
	public function Header() {
		// Set font
		$this->SetFont('pdfahelvetica', '', 10);
		// Title
		$this->setXY(10,5);
		$this->writeHTML($this->customHeaderTextName, true, false, false, false, '');
		$this->setXY(10,10);
		$this->writeHTML($this->customHeaderTextPosition, true, false, false, false, '');
		$this->setXY(10,15);

		$this->SetLineWidth(0.2);
		$this->SetDrawColor(27, 127, 176);
		$this->Line(10,16,200,16);
		// $this->Cell(1, 1, $this->customHeaderTextName, 0, false, 'T', 0, '', 0, false, 'M', 'M');
		// $this->Cell(0, 0, $this->customHeaderTextPosition, 0, false, 'T', 0, '', 0, false, 'M', 'M');
	}

	// Page footer
	public function Footer() {
		
		$this->SetLineWidth(0.2);
		$this->SetDrawColor(27, 127, 176);
		$this->Line(10,287,200,287);

		$this->setXY(168,288);
		$this->SetFont('pdfahelvetica', 'I', 8);
		$this->writeHTML('Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), true, false, false, false, '');

	}
	
}

class Generateapplicationpdf
{
	protected $ci;

	public function __construct()
	{
        $this->ci =& get_instance();
        $this->ci->load->model('applications_can_model');
        $this->ci->load->model('applicationsmodel');
	}

	public function generateApplication($idApplication=0){
		$application = $this->ci->applications_can_model->getAppFromUserToReview($idApplication);

		if ($application == false){
			echo "Error, application not found";
			die();
		}

		date_default_timezone_set('Europe/Madrid');
		$borderStyle = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(47, 76, 139));
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$pdf->SetCreator('PIOB');
		$pdf->SetAuthor('PIOB');
		$pdf->SetTitle('PIOB Application');
		// $pdf->SetSubject('TCPDF Tutorial');
		$pdf->SetKeywords('PIOB, APPLICATION, CANDIDATE');

		// set default header data
		//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 048', PDF_HEADER_STRING);

		// // set header and footer fonts
		// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setPrintHeader(true);
		$pdf->setPrintHeader(true);
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$l = Array();

		// PAGE META DESCRIPTORS --------------------------------------

		$l['a_meta_charset'] = 'UTF-8';
		$l['a_meta_dir'] = 'ltr';
		$l['a_meta_language'] = 'en';

		// TRANSLATIONS --------------------------------------
		$l['w_page'] = 'page';
			
		$pdf->setLanguageArray($l);

		// set font
		$pdf->SetFont('pdfahelvetica', 'B', 20);

		// add a page
		$pdf->customHeaderTextName = "Applicant: {$application->APP_DS_PREFIX} {$application->APP_DS_NAME} {$application->APP_DS_SURNAME}";
		$pdf->customHeaderTextPosition = "Position: {$application->POS_DS_NAME}";
		$pdf->AddPage();
		
		$pdf->SetFont('pdfahelvetica', '', 11);

		$pdf->setXY(10,25);
		//-------------------------
		//    Application Scope
		//-------------------------
		// $pdf->writeHTML($pdf->getY(), true, false, false, false, '');
		$pdf->SetFont('pdfahelvetica', '', 20);
		$pdf->SetTextColor(27, 127, 176);
		$pdf->writeHTML('<p>Application Scope</p>', true, false, false, false, '');
		$pdf->SetFont('pdfahelvetica', '', 11);
		$pdf->SetTextColor(10, 10, 10);
		$pdf->writeHTML($this->getReviewScope($application), true, false, false, false, '');
		//-------------------------
		//    Nominating Organization
		//-------------------------
		if ( $application->APP_DS_APPLICATIONFROM == 'Organization' ){
			if ($pdf->getY() > 200){
				$pdf->AddPage();
			}
			$pdf->SetFont('pdfahelvetica', '', 20);
			$pdf->SetTextColor(27, 127, 176);
			$pdf->writeHTML('<p>Nominating Organization</p>', true, false, false, false, '');
			$pdf->SetFont('pdfahelvetica', '', 11);
			$pdf->SetTextColor(10, 10, 10);
			$pdf->writeHTML($this->getReviewOrganization($application), true, false, false, false, '');
		}
		//-------------------------
		//    Experience / Educational Background
		//-------------------------
		if ($pdf->getY() > 200){
			$pdf->AddPage();
		}
		$pdf->SetFont('pdfahelvetica', '', 20);
		$pdf->SetTextColor(27, 127, 176);
		$pdf->writeHTML('<p>Experience / Educational Background</p>', true, false, false, false, '');
		$pdf->SetFont('pdfahelvetica', '', 11);
		$pdf->SetTextColor(10, 10, 10);
		$pdf->writeHTML($this->getReviewExperience($application), true, false, false, false, '');
		//-------------------------
		//    Motivation
		//-------------------------
		if ($pdf->getY() > 200){
			$pdf->AddPage();
		}
		$pdf->SetFont('pdfahelvetica', '', 20);
		$pdf->SetTextColor(27, 127, 176);
		$pdf->writeHTML('<p>Motivation</p>', true, false, false, false, '');
		$pdf->SetFont('pdfahelvetica', '', 11);
		$pdf->SetTextColor(10, 10, 10);
		$pdf->writeHTML($this->getReviewMotivation($application), true, false, false, false, '');

		$pdf->Output('piob_application.pdf', 'I');
	}

	public function generateApplicationAdmin($idApplication=0){
		$application = $this->ci->applicationsmodel->getAppFromUserToReview($idApplication);

		if ($application == false){
			echo "Error, application not found";
			die();
		}

		date_default_timezone_set('Europe/Madrid');
		$borderStyle = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(47, 76, 139));
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$pdf->SetCreator('PIOB');
		$pdf->SetAuthor('PIOB');
		$pdf->SetTitle('PIOB Application');
		// $pdf->SetSubject('TCPDF Tutorial');
		$pdf->SetKeywords('PIOB, APPLICATION, CANDIDATE');

		// set default header data
		//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 048', PDF_HEADER_STRING);

		// // set header and footer fonts
		// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setPrintHeader(true);
		$pdf->setPrintHeader(true);
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$l = Array();

		// PAGE META DESCRIPTORS --------------------------------------

		$l['a_meta_charset'] = 'UTF-8';
		$l['a_meta_dir'] = 'ltr';
		$l['a_meta_language'] = 'en';

		// TRANSLATIONS --------------------------------------
		$l['w_page'] = 'page';
			
		$pdf->setLanguageArray($l);

		// set font
		$pdf->SetFont('pdfahelvetica', 'B', 20);

		// add a page
		$pdf->customHeaderTextName = "Applicant: {$application->APP_DS_PREFIX} {$application->APP_DS_NAME} {$application->APP_DS_SURNAME}";
		$pdf->customHeaderTextPosition = "Position: {$application->POS_DS_NAME}";
		$pdf->AddPage();
		
		$pdf->SetFont('pdfahelvetica', '', 11);

		$pdf->setXY(10,25);
		//-------------------------
		//    Application Scope
		//-------------------------
		// $pdf->writeHTML($pdf->getY(), true, false, false, false, '');
		$pdf->SetFont('pdfahelvetica', '', 20);
		$pdf->SetTextColor(27, 127, 176);
		$pdf->writeHTML('<p>Application Scope</p>', true, false, false, false, '');
		$pdf->SetFont('pdfahelvetica', '', 11);
		$pdf->SetTextColor(10, 10, 10);
		$pdf->writeHTML($this->getReviewScope($application), true, false, false, false, '');
		//-------------------------
		//    Nominating Organization
		//-------------------------
		if ( $application->APP_DS_APPLICATIONFROM == 'Organization' ){
			if ($pdf->getY() > 200){
				$pdf->AddPage();
			}
			$pdf->SetFont('pdfahelvetica', '', 20);
			$pdf->SetTextColor(27, 127, 176);
			$pdf->writeHTML('<p>Nominating Organization</p>', true, false, false, false, '');
			$pdf->SetFont('pdfahelvetica', '', 11);
			$pdf->SetTextColor(10, 10, 10);
			$pdf->writeHTML($this->getReviewOrganization($application), true, false, false, false, '');
		}
		//-------------------------
		//    Experience / Educational Background
		//-------------------------
		if ($pdf->getY() > 200){
			$pdf->AddPage();
		}
		$pdf->SetFont('pdfahelvetica', '', 20);
		$pdf->SetTextColor(27, 127, 176);
		$pdf->writeHTML('<p>Experience / Educational Background</p>', true, false, false, false, '');
		$pdf->SetFont('pdfahelvetica', '', 11);
		$pdf->SetTextColor(10, 10, 10);
		$pdf->writeHTML($this->getReviewExperience($application), true, false, false, false, '');
		//-------------------------
		//    Motivation
		//-------------------------
		if ($pdf->getY() > 200){
			$pdf->AddPage();
		}
		$pdf->SetFont('pdfahelvetica', '', 20);
		$pdf->SetTextColor(27, 127, 176);
		$pdf->writeHTML('<p>Motivation</p>', true, false, false, false, '');
		$pdf->SetFont('pdfahelvetica', '', 11);
		$pdf->SetTextColor(10, 10, 10);
		$pdf->writeHTML($this->getReviewMotivation($application), true, false, false, false, '');

		$pdf->Output('piob_application.pdf', 'I');
	}

	private function getReviewScope($application){
		$response = "<br>";
		$response.= "<h4>Candidate Contact Information</h4>";
		$response.= "<p>";
		
		$name = "";
		if ( $application->APP_DS_PREFIX != 'Other' ){
			$name.= "{$application->APP_DS_PREFIX} ";
		}
		if ( $application->APP_DS_NAME == '' || $application->APP_DS_SURNAME == '' ){
			$name.= "<span style='color:red;'>Missing First name or Last name</span> ";
		}else{
			$name.= "{$application->APP_DS_NAME} {$application->APP_DS_SURNAME}";
		}
		$response.= "<strong class'fieldReview'>Name</strong>: {$name}<br>";
		$email = ($application->APP_DS_EMAIL != '') ? $application->APP_DS_EMAIL : "<span style='color:red;'>Missing Email</span> ";
		$response.= "<strong class'fieldReview'>Email</strong>: {$email}<br>";
		$cCode = ($application->APP_DS_COUNTRYCODE != '') ? $application->APP_DS_COUNTRYCODE : "<span style='color:red;'>Missing Country code</span> ";
		$response.= "<strong class'fieldReview'>Country code</strong>: {$cCode}<br>";
		$phone = ($application->APP_DS_PHONE != '') ? $application->APP_DS_PHONE : "<span style='color:red;'>Missing Phone</span> ";
		$response.= "<strong class'fieldReview'>Phone</strong>: {$phone}<br>";
		$gender = ($application->APP_DS_GENDER != '') ? $application->APP_DS_GENDER : "<span style='color:red;'>Missing Gender</span> ";
		$response.= "<strong class'fieldReview'>Gender</strong>: {$gender}<br>";
		
		$response.= "</p>";

		$response.= "<p>";
		$response.= "<strong class'fieldReview'>Address</strong>: <br>";
		$address = "";
		if ( $application->APP_DS_ADDRESS1 == '' ){
			$address= "<span style='color:red;'>Missing Addres 1</span> ";
		}else{
			$address= "{$application->APP_DS_ADDRESS1} {$application->APP_DS_ADDRESS2}";
		}
		$response.= "{$address}<br>";
		$city = ($application->APP_DS_CITY != '') ? $application->APP_DS_CITY : "<span style='color:red;'>Missing City</span> ";
		$state = ($application->APP_DS_STATE != '') ? $application->APP_DS_STATE : "<span style='color:red;'>Missing State</span> ";
		$response.= "{$city} ($state)<br>";
		$zip = ($application->APP_DS_POSTALCODE != '') ? $application->APP_DS_POSTALCODE : "<span style='color:red;'>Missing ZIP</span> ";
		$response.= "{$zip}<br>";
		$country = ($application->APP_DS_COUNTRY != '') ? $application->APP_DS_COUNTRY : "<span style='color:red;'>Missing Country</span> ";
		$response.= "{$country}<br>";
		$response.= "</p>";

		$response.= "<h4 class='font-weight-bold mb-6'>Position details</h4>";
		$response.= "<p>";
		$position = ($application->POS_CO_ID != 0) ? $application->POS_DS_NAME : "<span style='color:red;'>Missing Position</span> ";
		$response.= "<strong class'fieldReview'>Position</strong>: {$position}<br>";
		$tposition = ($application->TPS_CO_ID != 0) ? $application->TPS_DS_NAME : "<span style='color:red;'>Missing Type position</span> ";
		$response.= "<strong class'fieldReview'>Application Type</strong>: {$tposition}<br>";
		$appFrom = "";
		if ( $application->APP_DS_APPLICATIONFROM =='' ){
			$appFrom = "<span style='color:red;'>Missing Application from</span>";
		}else{
			if ( $application->APP_DS_APPLICATIONFROM == 'Individual'){
				$appFrom = "Individual (application)";
			}else{
				$appFrom = "Organization (nomination)";
			}
		}
		$response.= "<strong class'fieldReview'>Application From</strong>: {$appFrom}<br>";
		if ( $application->APP_DS_APPLICATIONFROM == 'Organization'){
			$nomFrom = ($application->APP_DS_NOMINATIONFROM != '') ? $application->APP_DS_NOMINATIONFROM : "<span style='color:red;'>Missing Nomination From</span> ";
			$response.= "<strong class'fieldReview'>Nomination From</strong>: {$nomFrom}<br>";
		}
		$response.= "</p>";

		$response.= "<h4 class='font-weight-bold mb-6'>Travel Costs and Remuneration</h4>";
		$response.= "<p>";
		$travelCost = ($application->APP_DS_TRAVELCOST != '') ? $application->APP_DS_TRAVELCOST : "<span style='color:red;'>Missing Travel cost</span> ";
		$response.= "<strong class'fieldReview'>Do you wish to apply for your travel costs to be covered by the Standard-Setting Board?</strong>: {$travelCost}<br>";
		$stipend = ($application->APP_DS_STIPEND != '') ? $application->APP_DS_STIPEND : "<span style='color:red;'>Missing Stipend</span> ";
		$response.= "<strong class'fieldReview'>Do you wish to apply for a stipend?</strong>: {$stipend}<br>";
		$reapplication = ($application->APP_DS_REAPPLICATION != '') ? $application->APP_DS_REAPPLICATION : "<span style='color:red;'>Missing Re-application</span> ";
		$response.= "<strong class'fieldReview'>Re-application</strong>: {$reapplication}<br>";
		$prevapplication = ($application->APP_DS_PREVIUSAPPLICATION != '') ? $application->APP_DS_PREVIUSAPPLICATION : "<span style='color:red;'>Missing Previous application</span> ";
		$response.= "<strong class'fieldReview'>Previous Application(s)</strong>: {$prevapplication}<br>";
		if ( $application->APP_DS_PREVIUSAPPLICATION == 'Yes' ){
			$prevAppData = $this->getPrevAppData();
			$response.= " <br>{$prevAppData}<br>";
		}
		
		$response.= "</p>";

		$response.= "<h4 class='font-weight-bold mb-6'>Previous experience with the IAASB and/or IESBA</h4>";
		$response.= "<p>";
		$prevExp = $this->getPrevExperience();
		$response.= "<br>{$prevExp}<br>";
		$response.= "</p>";

		return $response;
	}

	private function getPrevExperience(){
		$elements = $this->ci->applications_can_model->getPreviousExperience();
		if ($elements == false){
			return "The candidate does not have any previous experience with IAASB and/or IESBA";
		}else{
			$response = '<table class="table table-sm table-hover mb-6">';
			$response.= '<thead>';
			$response.= '<tr>';
			$response.= '<th scope="col">Start</th>';
			$response.= '<th scope="col">End</th>';
			$response.= '<th scope="col">Board/Committee</th>';
			$response.= '<th scope="col">Role</th>';
			$response.= '</tr>';
			$response.= '<tbody>';

			foreach ($elements->result() as $element) {
				$response.= "<tr><td>{$element->APE_DS_START}</td>";
				$response.= "<td>{$element->APE_DS_END}</td>";
				$response.= "<td>{$element->APE_DS_BOARD}</td>";
				$response.= "<td>{$element->APE_DS_ROLE}</td></tr>";
			}
			$response.= '</tbody>';
			$response.= '</table>';

			return $response;
		}
	}

	private function getPrevAppData(){
		$elements = $this->ci->applications_can_model->getPreviousNomination();
		if ($elements == false){
			return "<span style='color:red;'>Missing Previous nominations</span>";
		}else{
			$response = '<table class="table table-sm table-hover mb-6">';
			$response.= '<thead>';
			$response.= '<tr>';
			$response.= '<th scope="col">Year</th>';
			$response.= '<th scope="col">Position</th>';
			$response.= '<th scope="col">Sucessful</th>';
			$response.= '</tr>';
			$response.= '<tbody>';

			foreach ($elements->result() as $element) {
				$response.= "<tr><td>{$element->APN_DS_YEAR}</td>";
				$response.= "<td>{$element->POS_DS_NAME}</td>";
				$response.= "<td>{$element->APN_DS_SUCCESSFUL}</td></tr>";
			}
			$response.= '</tbody>';
			$response.= '</table>';

			return $response;
		}
	}

	private function getReviewOrganization($application){
		$response = "<br>";
		$response.= "<h4 class='font-weight-bold mb-6'>Organization Contact Information</h4>";
		$response.= "<p>";
		$name = "";
		if ( $application->APP_DS_CEOSALUDATION != 'Other' ){
			$name.= "{$application->APP_DS_CEOSALUDATION} ";
		}
		if ( $application->APP_DS_CEONAME == '' || $application->APP_DS_CEOSURNAME == '' ){
			$name.= "<span class='text-danger'>Missing First name or Last name</span> ";
		}else{
			$name.= "{$application->APP_DS_CEONAME} {$application->APP_DS_CEOSURNAME}";
		}
		$response.= "<strong class'fieldReview'>CEO (or equivalent)</strong>: {$name}<br>";
		$position = ($application->APP_DS_CEOPOSITION != '') ? $application->APP_DS_CEOPOSITION : "<span class='text-danger'>Missing CEO position</span> ";
		$response.= "<strong class'fieldReview'>Position</strong>: {$position}<br>";
		$email = ($application->APP_DS_ORGMAIL != '') ? $application->APP_DS_ORGMAIL : "<span class='text-danger'>Missing Email</span> ";
		$response.= "<strong class'fieldReview'>Email</strong>: {$email}<br>";
		$cCode = ($application->APP_DS_ORGCOUNTRYCODE != '') ? $application->APP_DS_ORGCOUNTRYCODE : "<span class='text-danger'>Missing Country code</span> ";
		$response.= "<strong class'fieldReview'>Country code</strong>: {$cCode}<br>";
		$phone = ($application->APP_DS_ORGPHONE != '') ? $application->APP_DS_ORGPHONE : "<span class='text-danger'>Missing Phone</span> ";
		$response.= "<strong class'fieldReview'>Phone</strong>: {$phone}<br>";
		$response.= "<strong class'fieldReview'>Fax</strong>: {$application->APP_DS_ORGFAX}<br>";
		$response.= "</p>";
		if ($application->APP_BL_OTHERCONTACT == 1) {
			$response.= "<p>";
			$name = "";
			if ( $application->APP_DS_MANAGERSALUDATION != 'Other' ){
				$name.= "{$application->APP_DS_MANAGERSALUDATION} ";
			}
			if ( $application->APP_DS_MANAGERNAME == '' || $application->APP_DS_MANAGERSURNAME == '' ){
				$name.= "<span class='text-danger'>Missing First name or Last name</span> ";
			}else{
				$name.= "{$application->APP_DS_MANAGERNAME} {$application->APP_DS_MANAGERSURNAME}";
			}
			$response.= "<strong class'fieldReview'>Manager</strong>: {$name}<br>";
			$position = ($application->APP_DS_MANAGERPOSITION != '') ? $application->APP_DS_MANAGERPOSITION : "<span class='text-danger'>Missing Manager position</span> ";
			$response.= "<strong class'fieldReview'>Position</strong>: {$position}<br>";
			$email = ($application->APP_DS_MANAGERMAIL != '') ? $application->APP_DS_MANAGERMAIL : "<span class='text-danger'>Missing Manager email</span> ";
			$response.= "<strong class'fieldReview'>Email</strong>: {$email}<br>";
			$phone = ($application->APP_DS_MANAGERPHONE != '') ? $application->APP_DS_MANAGERPHONE : "<span class='text-danger'>Missing Manager phone</span> ";
			$response.= "<strong class'fieldReview'>Phone</strong>: {$phone}<br>";
			$response.= "</p>";
		}

		$response.= "<p>";
		$response.= "<strong class'fieldReview'>Address</strong>: <br>";
		$address = "";
		if ( $application->APP_DS_ORGADDRESS1 == '' ){
			$address= "<span class='text-danger'>Missing Addres 1</span> ";
		}else{
			$address= "{$application->APP_DS_ORGADDRESS1} {$application->APP_DS_ORGADDRESS2}";
		}
		$response.= "{$address}<br>";
		$city = ($application->APP_DS_ORGCITY != '') ? $application->APP_DS_ORGCITY : "<span class='text-danger'>Missing City</span> ";
		$state = ($application->APP_DS_ORGSTATE != '') ? $application->APP_DS_ORGSTATE : "<span class='text-danger'>Missing State</span> ";
		$response.= "{$city} ($state)<br>";
		$zip = ($application->APP_DS_ORGPOSTALCODE != '') ? $application->APP_DS_ORGPOSTALCODE : "<span class='text-danger'>Missing ZIP</span> ";
		$response.= "{$zip}<br>";
		$country = ($application->APP_DS_ORGCOUNTRY != '') ? $application->APP_DS_ORGCOUNTRY : "<span class='text-danger'>Missing Country</span> ";
		$response.= "{$country}<br>";
		$response.= "</p>";

		$response.= "<h4 class='font-weight-bold mb-6'>Disciplinary Action</h4>";
		$response.= "<p>";
		$disciplinary = ($application->APP_DS_ORGDISCIPLINARY != '') ? $application->APP_DS_ORGDISCIPLINARY : "<span class='text-danger'>Missing Disciplinary action</span> ";
		$response.= "<strong class'fieldReview'>Received / Aware of any complaints</strong>: {$disciplinary}<br>";
		if ( $application->APP_DS_ORGDISCIPLINARY == 'Yes' ){
			$details = ($application->APP_DS_ORGDISCIPLINARYDETAILS != '') ? str_replace(PHP_EOL,"<br>",$application->APP_DS_ORGDISCIPLINARYDETAILS) : "<span class='text-danger'>Missing Disciplinary details</span> ";
			$response.= "<strong class'fieldReview'>Additional Details</strong><br>";
			$response.= "{$details}<br>";
		}
		
		$response.= "</p>";
		return $response;
	}

	private function getReviewExperience($application){
		$response = "<br>";
		$response.= "<h4 class='font-weight-bold mb-6'>Current Position</h4>";

		$response.= "<p>";
		$response.= "<strong class'fieldReview'>Current Employing Organization</strong>: <br>";
		$currentEmp = ($application->APP_DS_CURRENTEMPLOYE != '') ? $application->APP_DS_CURRENTEMPLOYE : "<span class='text-danger'>Missing Current employing</span> ";
		$response.= "{$currentEmp}<br>";
		$response.= "<strong class'fieldReview'>Current Employing Organization's Website URL</strong>: <br>";
		$currentWeb = ($application->APP_DS_CURRENTEMPLOYEWEBSITE != '') ? $application->APP_DS_CURRENTEMPLOYEWEBSITE : "<span class='text-danger'>Missing Website</span> ";
		$response.= "{$currentWeb}<br>";
		$response.= "<strong class'fieldReview'>Current Position Title</strong>: <br>";
		$currentTitle = ($application->APP_DS_CURRENTPOSITION != '') ? $application->APP_DS_CURRENTPOSITION : "<span class='text-danger'>Missing Position title</span> ";
		$response.= "{$currentTitle}<br>";
		$response.= "<strong class'fieldReview'>Start Date</strong>: <br>";
		$currentStart = ($application->APP_DS_CURRENTSTARTDATE != '') ? $application->APP_DS_CURRENTSTARTDATE : "<span class='text-danger'>Missing Start Date</span> ";
		$response.= "{$currentStart}<br>";
		$response.= "</p>";

		$response.= "<h4 class='font-weight-bold mb-6'>Past Experience</h4>";

		$response.= "<p>";
		$response.= "<strong class'fieldReview'>Country where the majority of professional experience was obtained</strong>: <br>";
		$country = ($application->APP_DS_COUNTRYEXPERIENCE != '') ? $application->APP_DS_COUNTRYEXPERIENCE : "<span class='text-danger'>Missing Country</span> ";
		$response.= "{$country}<br>";
		$experience = $this->getPastExperience();
		$response.= "<br>{$experience}<br>";
		$response.= "</p>";

		$response.= "<h4 class='font-weight-bold mb-6'>Regional Involvement</h4>";

		$response.= "<p>";
		$response.= "<strong class'fieldReview'>Volunteer or remunerated service relevant to the position</strong>: <br>";
		$relevantExperience = ($application->APP_DS_RELEVANTSERVICES != '') ? str_replace(PHP_EOL,"<br>",$application->APP_DS_RELEVANTSERVICES) : "NA ";
		$response.= "{$relevantExperience}<br>";
		$response.= "</p>";

		$response.= "<h4 class='font-weight-bold mb-6'>Post-Secondary Education</h4>";

		$response.= "<p>";
		$postSecondary = $this->getPostSecondary();
		$response.= "<br>{$postSecondary}<br>";
		$response.= "</p>";

		$response.= "<h4 class='font-weight-bold mb-6'>Professional Qualifications</h4>";

		$response.= "<p>";
		$revQualifications = $this->getReviewQualifications();
		$response.= "<br>{$revQualifications}<br><br>";
		$response.= "<strong class'fieldReview'>Professional Affiliations</strong>: <br>";
		$revAffiliations = ($application->APP_DS_PROFESSIONALAFFILIATIONS != '') ? str_replace(PHP_EOL,"<br>",$application->APP_DS_PROFESSIONALAFFILIATIONS) : "None ";
		$response.= "{$revAffiliations}<br>";
		$response.= "</p>";

		$response.= "<h4 class='font-weight-bold mb-6'>Candidate Language Skills</h4>";

		$response.= "<p>";
		$primaryLanguage = ($application->APP_DS_PRIMARYLANGUAGE != '') ? $application->APP_DS_PRIMARYLANGUAGE : "<span class='text-danger'>Missing Primary language</span> ";
		$response.= "<strong class'fieldReview'>Primary Language</strong>: {$primaryLanguage}<br>";
		$writeEnglish = ($application->APP_DS_WRITTENENGLISH != '') ? $application->APP_DS_WRITTENENGLISH : "<span class='text-danger'>Missing Written english proficiency</span> ";
		$response.= "<strong class'fieldReview'>Written English Proficiency</strong>: {$writeEnglish}<br>";
		$spokenEnglish = ($application->APP_DS_SPOKENENGLISH != '') ? $application->APP_DS_SPOKENENGLISH : "<span class='text-danger'>Missing Spoken english proficiency</span> ";
		$response.= "<strong class'fieldReview'>Spoken English Proficiency</strong>: {$spokenEnglish}<br>";
		$response.= "<strong class'fieldReview'>Other Language Skills</strong>: <br>";
		$otherLanguage = $this->getReviewOtherLanguage();
		$response.= "<br>{$otherLanguage}<br>";
		$response.= "</p>";
		
		return $response;
	}

	private function getPastExperience(){
		$elements = $this->ci->applications_can_model->getPrevExp();
		if ($elements == false){
			return "<span class='text-danger'>Missing Experience</span>";
		}else{
			$response = '<table class="table table-sm table-hover mb-6">';
			$response.= '<thead>';
			$response.= '<tr>';
			$response.= '<th scope="col">Start</th>';
			$response.= '<th scope="col">End</th>';
			$response.= '<th scope="col">Position</th>';
			$response.= '<th scope="col">Organization</th>';
			$response.= '<th scope="col">Location</th>';
			$response.= '</tr>';
			$response.= '<tbody>';

			foreach ($elements->result() as $element) {
				$response.= "<tr><td>{$element->APX_DS_START}</td>";
				$response.= "<td>{$element->APX_DS_END}</td>";
				$response.= "<td>{$element->APX_DS_POSITION}</td>";
				$response.= "<td>{$element->APX_DS_ORGANIZATION}</td>";
				$response.= "<td>{$element->APX_DS_COUNTRY}</td></tr>";
			}
			$response.= '</tbody>';
			$response.= '</table>';

			return $response;
		}
	}

	private function getPostSecondary(){
		$elements = $this->ci->applications_can_model->getPrevDegree();
		if ($elements == false){
			return "<span class='text-danger'>Missing Education</span>";
		}else{
			$response = '<table class="table table-sm table-hover mb-6">';
			$response.= '<thead>';
			$response.= '<tr>';
			$response.= '<th scope="col">Awarded</th>';
			$response.= '<th scope="col">Degree</th>';
			$response.= '<th scope="col">Major/Subject</th>';
			$response.= '<th scope="col">Institution</th>';
			$response.= '<th scope="col">Location</th>';
			$response.= '</tr>';
			$response.= '<tbody>';

			foreach ($elements->result() as $element) {
				$response.= "<tr><td>{$element->ADE_DS_AWARDED}</td>";
				$response.= "<td>{$element->ADE_DS_DEGREE}</td>";
				$response.= "<td>{$element->ADE_DS_MAJOR}</td>";
				$response.= "<td>{$element->ADE_DS_INSTITUTION}</td>";
				$response.= "<td>{$element->ADE_DS_LOCATION}</td></tr>";
			}
			$response.= '</tbody>';
			$response.= '</table>';

			return $response;
		}
	}

	private function getReviewQualifications(){
		$elements = $this->ci->applications_can_model->getQualifications();
		if ($elements == false){
			return "The candidate does not have any professional qualifications";
		}else{
			$response = '<table class="table table-sm table-hover mb-6">';
			$response.= '<thead>';
			$response.= '<tr>';
			$response.= '<th scope="col">Awarded</th>';
			$response.= '<th scope="col">Qualification</th>';
			$response.= '<th scope="col">Institution</th>';
			$response.= '<th scope="col">Location</th>';
			$response.= '</tr>';
			$response.= '<tbody>';

			foreach ($elements->result() as $element) {
				$response.= "<tr><td>{$element->AQU_DS_AWARDED}</td>";
				$response.= "<td>{$element->AQU_DS_QUALIFICATION}</td>";
				$response.= "<td>{$element->AQU_DS_INSTITUTION}</td>";
				$response.= "<td>{$element->AQU_DS_LOCATION}</td></tr>";
			}
			$response.= '</tbody>';
			$response.= '</table>';

			return $response;
		}
	}

	private function getReviewOtherLanguage(){
		$elements = $this->ci->applications_can_model->getLanguages();
		if ($elements == false){
			return "The candidate does not speak or write other langages";
		}else{
			$response = '<table class="table table-sm table-hover mb-6">';
			$response.= '<thead>';
			$response.= '<tr>';
			$response.= '<th scope="col">Language</th>';
			$response.= '<th scope="col">Writte</th>';
			$response.= '<th scope="col">Spoke</th>';
			$response.= '</tr>';
			$response.= '<tbody>';

			foreach ($elements->result() as $element) {
				$response.= "<tr><td>{$element->ALA_DS_LANGUAGE}</td>";
				$response.= "<td>{$element->ALA_DS_WRITE}</td>";
				$response.= "<td>{$element->ALA_DS_SPOKE}</td></tr>";
			}
			$response.= '</tbody>';
			$response.= '</table>';

			return $response;
		}
	}

	private function getReviewMotivation($application){
		$response = "<br>";
		$response.= "<p>";
		$response.= "<strong class'fieldReview'>Interest & Objectives</strong><br><br>";
		$interest = ($application->APP_DS_INTERESTAPPLY != '') ? str_replace(PHP_EOL,"<br>",$application->APP_DS_INTERESTAPPLY) : "<span class='text-danger'>Missing Interest</span> ";
		$response.= "{$interest}<br><br>";
		$response.= "<strong class'fieldReview'>Objectives for Position</strong><br><br>";
		$objectives = ($application->APP_DS_OBJECTIVESPOSITION != '') ? str_replace(PHP_EOL,"<br>",$application->APP_DS_OBJECTIVESPOSITION) : "<span class='text-danger'>Missing Objectives</span> ";
		$response.= "{$objectives}<br><br>";
		$response.= "<strong class'fieldReview'>Attributes and Achievements Relevant to the Position</strong><br><br>";
		$attributes = ($application->APP_DS_RELEVANTSKILLS != '') ? str_replace(PHP_EOL,"<br>",$application->APP_DS_RELEVANTSKILLS) : "<span class='text-danger'>Missing Attributes</span> ";
		$response.= "{$attributes}<br><br>";
		$response.= "<strong class'fieldReview'>Relevant Archievements</strong><br><br>";
		$archievement = ($application->APP_DS_RELEVANTARCHIEVEMENT != '') ? str_replace(PHP_EOL,"<br>",$application->APP_DS_RELEVANTARCHIEVEMENT) : "<span class='text-danger'>Missing Archievements</span> ";
		$response.= "{$archievement}<br><br>";
		$response.= "</p>";
		
		return $response;
	}


}

/* End of file Generainformevaloracion.php */
/* Location: ./application/libraries/Generainformevaloracion.php */
