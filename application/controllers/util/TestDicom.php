<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TestDicom extends CI_Controller
{
    function __construct(){
		parent::__construct();
	}

    public function index()
    {
        $this->load->library('dicom');
        echo "TEST DICOM<br><br>";
        $file = FCPATH . 'dcmtest/0.dcm';
        $result = $this->dicom->getBasicMetadata($file);

        if (!$result['success']) {
            echo $result['error'];
        } else {
            echo '<pre>';
            print_r($result['metadata']);
            echo '</pre>';
        }

        $result = $this->dicom->toJson($file);
        echo '<pre>';
        print_r($result);
        echo '</pre>';
    }
}