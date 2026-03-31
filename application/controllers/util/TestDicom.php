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

    public function test_envio()
    {
        $this->load->library('dicom');

        $host = defined('PACS_HOST') ? (string)PACS_HOST : 'segundaopinionradiologica.actualpacs.com';
        $port = defined('PACS_PORT') ? (int)PACS_PORT : 5419;
        $calledAet = defined('PACS_CALLED_AET') ? (string)PACS_CALLED_AET : 'SEGUNDAOPINION';
        $callingAet = defined('PACS_CALLING_AET') ? (string)PACS_CALLING_AET : '';

        $result = $this->dicom->sendToPacs(
            $host,
            $port,
            '/var/www/vhosts/desarrolloinformatico.com/2op.desarrolloinformatico.com/dcmtest/out_test_dicom',
            $calledAet,
            $callingAet,
            array('-v')
        );

        echo '<pre>';
        print_r($result);
        echo '</pre>';
    }

    /**
     * Prueba integral de la libreria Dicom.
     *
     * Uso sugerido:
     * /util/TestDicom/test_all
     * /util/TestDicom/test_all?pacs_host=127.0.0.1&pacs_port=104&pacs_called_aet=ANY-SCP&pacs_calling_aet=ANY-SCU
     *
     * @return void
     */
    public function test_all()
    {
        $this->load->library('dicom');

        $baseDir = rtrim(FCPATH, '/') . '/dcmtest';
        $dicomInput = $baseDir . '/0.dcm';
        $pdfInput = $baseDir . '/input.pdf';
        $jpgInput = $baseDir . '/input.jpg';
        $pngInput = $baseDir . '/input.png';

        $workDir = $baseDir . '/out_test_dicom';
        if (!is_dir($workDir)) {
            @mkdir($workDir, 0775, true);
        }

        $previewOut = $workDir . '/preview.png';
        $dicomTagCopy = $workDir . '/copy_for_modify.dcm';
        $pdfDicomOut = $workDir . '/from_pdf.dcm';
        $pdfDicomOutWithStudy = $workDir . '/from_pdf_with_study.dcm';
        $jpegDicomOut = $workDir . '/from_jpeg.dcm';
        $pngJpegOut = $workDir . '/from_png.jpg';
        $pngDicomOut = $workDir . '/from_png.dcm';

        header('Content-Type: text/html; charset=utf-8');
        echo '<h2>Test integral libreria Dicom</h2>';
        echo '<pre>';

        $this->printTestStep('Entorno', array(
            'base_dir' => $baseDir,
            'work_dir' => $workDir,
            'dicom_input' => $dicomInput,
            'pdf_input' => $pdfInput,
            'jpg_input' => $jpgInput,
            'png_input' => $pngInput,
        ));

        $this->printDicomResult('ping', $this->dicom->ping());

        if (file_exists($dicomInput)) {
            $this->printDicomResult('dump', $this->dicom->dump($dicomInput));

            $dumpRaw = $this->dicom->dumpRaw($dicomInput);
            $this->printRawResult('dumpRaw', $dumpRaw !== false, $dumpRaw === false ? $this->dicom->getLastError() : substr($dumpRaw, 0, 500));

            $this->printDicomResult('toJson', $this->dicom->toJson($dicomInput));
            $this->printDicomResult('createPreview', $this->dicom->createPreview($dicomInput, $previewOut, 'png'));
            $this->printDicomResult('getBasicMetadata', $this->dicom->getBasicMetadata($dicomInput));
            $this->printRawResult('isDicom', (bool) $this->dicom->isDicom($dicomInput), $this->dicom->isDicom($dicomInput) ? 'true' : 'false');

            if (@copy($dicomInput, $dicomTagCopy)) {
                $this->printDicomResult('modifyTag', $this->dicom->modifyTag($dicomTagCopy, 'StudyDescription', 'TEST_DESDE_TESTDICOM', true));
                $metadataAfterModify = $this->dicom->getBasicMetadata($dicomTagCopy);
                if (!empty($metadataAfterModify['success'])) {
                    $extracted = isset($metadataAfterModify['metadata']['study_description']) ? $metadataAfterModify['metadata']['study_description'] : null;
                    $this->printRawResult('extractTagValue (via getBasicMetadata)', true, $extracted);
                } else {
                    $this->printRawResult('extractTagValue (via getBasicMetadata)', false, $metadataAfterModify['error']);
                }
            } else {
                $this->printSkip('modifyTag', 'No se pudo crear copia temporal del DICOM de entrada.');
            }
        } else {
            $this->printSkip('Bloque DICOM base', 'No existe archivo de entrada: ' . $dicomInput);
        }

        if (file_exists($pdfInput)) {
            $this->printDicomResult(
                'convertPdfToDicom',
                $this->dicom->convertPdfToDicom($pdfInput, $pdfDicomOut, array(
                    'patient_name' => 'Paciente Test',
                    'patient_id' => 'PT-001',
                    'study_date' => date('Ymd'),
                    'study_time' => date('His'),
                    'study_description' => 'PDF test convertPdfToDicom',
                ))
            );

            $sourceForStudy = file_exists($dicomInput) ? $dicomInput : null;
            $this->printDicomResult(
                'createPdfDicom',
                $this->dicom->createPdfDicom(
                    $pdfInput,
                    $pdfDicomOutWithStudy,
                    'Paciente Test 2',
                    'PT-002',
                    date('Ymd'),
                    date('His'),
                    'PDF test createPdfDicom',
                    null,
                    $sourceForStudy
                )
            );
        } else {
            $this->printSkip('convertPdfToDicom/createPdfDicom', 'No existe archivo PDF de entrada: ' . $pdfInput);
        }

        if (file_exists($jpgInput)) {
            $this->printDicomResult(
                'convertJpegToDicom',
                $this->dicom->convertJpegToDicom($jpgInput, $jpegDicomOut, array(
                    'patient_name' => 'Paciente JPG',
                    'patient_id' => 'PJ-001',
                    'study_date' => date('Ymd'),
                    'study_time' => date('His'),
                    'study_description' => 'JPG test img2dcm',
                ))
            );
        } else {
            $this->printSkip('convertJpegToDicom', 'No existe archivo JPG de entrada: ' . $jpgInput);
        }

        if (file_exists($pngInput)) {
            $this->printDicomResult('convertPngToJpeg', $this->dicom->convertPngToJpeg($pngInput, $pngJpegOut));
            $this->printDicomResult(
                'convertPngToDicom',
                $this->dicom->convertPngToDicom($pngInput, $pngDicomOut, array(
                    'patient_name' => 'Paciente PNG',
                    'patient_id' => 'PP-001',
                    'study_date' => date('Ymd'),
                    'study_time' => date('His'),
                    'study_description' => 'PNG test ImageMagick + img2dcm',
                ))
            );
        } else {
            $this->printSkip('convertPngToJpeg/convertPngToDicom', 'No existe archivo PNG de entrada: ' . $pngInput);
        }

        $this->printRawResult('detectFileType (dicom)', file_exists($dicomInput), file_exists($dicomInput) ? $this->dicom->detectFileType($dicomInput) : 'N/A');
        $this->printRawResult('detectFileType (pdf)', file_exists($pdfInput), file_exists($pdfInput) ? $this->dicom->detectFileType($pdfInput) : 'N/A');
        $this->printRawResult('detectFileType (jpg)', file_exists($jpgInput), file_exists($jpgInput) ? $this->dicom->detectFileType($jpgInput) : 'N/A');
        $this->printRawResult('detectFileType (png)', file_exists($pngInput), file_exists($pngInput) ? $this->dicom->detectFileType($pngInput) : 'N/A');

        $this->printDicomResult('listFilesInDirectory', $this->dicom->listFilesInDirectory($baseDir, true));

        $pacsHost = trim((string) $this->input->get('pacs_host', true));
        $pacsPort = (int) $this->input->get('pacs_port', true);
        $pacsCalledAet = (string) $this->input->get('pacs_called_aet', true);
        $pacsCallingAet = (string) $this->input->get('pacs_calling_aet', true);

        if ($pacsHost !== '' && $pacsPort > 0) {
            if ($pacsCalledAet === '') {
                $pacsCalledAet = 'ANY-SCP';
            }
            if ($pacsCallingAet === '') {
                $pacsCallingAet = 'ANY-SCU';
            }

            $filesToSend = array();
            foreach (array($dicomInput, $pdfDicomOut, $pdfDicomOutWithStudy, $jpegDicomOut, $pngDicomOut) as $candidate) {
                if (file_exists($candidate)) {
                    $filesToSend[] = $candidate;
                }
            }

            if (!empty($filesToSend)) {
                $this->printDicomResult('sendToPacs', $this->dicom->sendToPacs($pacsHost, $pacsPort, $filesToSend, $pacsCalledAet, $pacsCallingAet));
            } else {
                $this->printSkip('sendToPacs', 'No hay DICOM generados/validos para enviar.');
            }
        } else {
            $this->printSkip('sendToPacs', 'No se ejecuta envio PACS si no se pasan pacs_host y pacs_port por query string.');
        }

        echo "\nFin de pruebas\n";
        echo '</pre>';
    }

    /**
     * Imprime resultados de un metodo estandar de la libreria.
     *
     * @param string $name
     * @param array $result
     * @return void
     */
    protected function printDicomResult($name, $result)
    {
        $ok = !empty($result['success']);
        echo '[' . ($ok ? 'OK' : 'ERROR') . '] ' . $name . "\n";
        print_r($result);
        echo "\n";
    }

    /**
     * Imprime un bloque informativo de contexto de prueba.
     *
     * @param string $name
     * @param array $payload
     * @return void
     */
    protected function printTestStep($name, $payload)
    {
        echo '[INFO] ' . $name . "\n";
        print_r($payload);
        echo "\n";
    }

    /**
     * Imprime resultado simple no estandarizado.
     *
     * @param string $name
     * @param bool $ok
     * @param mixed $payload
     * @return void
     */
    protected function printRawResult($name, $ok, $payload)
    {
        echo '[' . ($ok ? 'OK' : 'ERROR') . '] ' . $name . "\n";
        print_r($payload);
        echo "\n";
    }

    /**
     * Marca un test como omitido.
     *
     * @param string $name
     * @param string $reason
     * @return void
     */
    protected function printSkip($name, $reason)
    {
        echo '[SKIP] ' . $name . "\n";
        echo $reason . "\n\n";
    }
}