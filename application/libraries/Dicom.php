<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dicom
{
    /**
     * Instancia de CodeIgniter.
     *
     * @var CI_Controller
     */
    protected $CI;

    /**
     * Ruta base de binarios DCMTK.
     *
     * @var string
     */
    protected $dcmtkPath = '/var/www/vhosts/desarrolloinformatico.com/dcmtk-3.7.0/bin';

    /**
     * Último error producido por la librería.
     *
     * @var string
     */
    protected $lastError = '';

    public function __construct($params = array())
    {
        $this->CI =& get_instance();

        if (!empty($params['dcmtk_path'])) {
            $this->dcmtkPath = rtrim($params['dcmtk_path'], '/');
        }
    }

    /**
     * Devuelve el último error.
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Limpia el último error.
     *
     * @return void
     */
    protected function clearError()
    {
        $this->lastError = '';
    }

    /**
     * Guarda un error interno.
     *
     * @param string $message
     * @return void
     */
    protected function setError($message)
    {
        $this->lastError = (string) $message;
        log_message('error', 'Dicom library: ' . $this->lastError);
    }

    /**
     * Devuelve la ruta absoluta a un binario DCMTK.
     *
     * @param string $binary
     * @return string
     */
    protected function getBinaryPath($binary)
    {
        return $this->dcmtkPath . '/' . ltrim($binary, '/');
    }

    /**
     * Comprueba si existe un archivo.
     *
     * @param string $filePath
     * @return bool
     */
    protected function validateFile($filePath)
    {
        if (empty($filePath)) {
            $this->setError('La ruta del archivo está vacía.');
            return false;
        }

        if (!file_exists($filePath)) {
            $this->setError('El archivo no existe: ' . $filePath);
            return false;
        }

        if (!is_readable($filePath)) {
            $this->setError('El archivo no tiene permisos de lectura: ' . $filePath);
            return false;
        }

        return true;
    }

    /**
     * Comprueba si existe un binario de DCMTK.
     *
     * @param string $binary
     * @return bool
     */
    protected function validateBinary($binary)
    {
        $binaryPath = $this->getBinaryPath($binary);

        if (!file_exists($binaryPath)) {
            $this->setError('No existe el binario DCMTK: ' . $binaryPath);
            return false;
        }

        if (!is_executable($binaryPath)) {
            $this->setError('El binario DCMTK no es ejecutable: ' . $binaryPath);
            return false;
        }

        return true;
    }

    /**
     * Ejecuta un comando y devuelve salida, código y comando.
     *
     * @param string $binary
     * @param array $args
     * @return array
     */
    protected function runCommand($binary, array $args = array())
    {
        $this->clearError();

        if (!$this->validateBinary($binary)) {
            return array(
                'success' => false,
                'command' => null,
                'output'  => '',
                'code'    => null,
                'error'   => $this->getLastError(),
            );
        }

        $binaryPath = $this->getBinaryPath($binary);

        $escapedArgs = array();
        foreach ($args as $arg) {
            $escapedArgs[] = escapeshellarg((string) $arg);
        }
        $env = [
            'DCMDICTPATH=/var/www/vhosts/desarrolloinformatico.com/dcmtk-3.7.0/share/dcmtk-3.7.0/dicom.dic',
            'DCMICONVPATH=/var/www/vhosts/desarrolloinformatico.com/dcmtk-3.7.0/share/dcmtk-3.7.0'
        ];

        $command = implode(' ', $env) . ' ' . escapeshellcmd($binaryPath) . ' ' . implode(' ', $escapedArgs) . ' 2>&1';
        //$command = escapeshellcmd($binaryPath) . ' ' . implode(' ', $escapedArgs) . ' 2>&1';

        $outputLines = array();
        $exitCode = 1;

        exec($command, $outputLines, $exitCode);

        $output = implode("\n", $outputLines);

        if ($exitCode !== 0) {
            $this->setError('Error ejecutando comando DCMTK. Código: ' . $exitCode . '. Salida: ' . $output);
            return array(
                'success' => false,
                'command' => $command,
                'output'  => $output,
                'code'    => $exitCode,
                'error'   => $this->getLastError(),
            );
        }

        return array(
            'success' => true,
            'command' => $command,
            'output'  => $output,
            'code'    => $exitCode,
            'error'   => '',
        );
    }

    /**
     * Comprueba que DCMTK responde.
     *
     * @return array
     */
    public function ping()
    {
        return $this->runCommand('dcmdump', array('--version'));
    }

    /**
     * Ejecuta dcmdump sobre un fichero DICOM.
     *
     * @param string $filePath
     * @return array
     */
    public function dump($filePath)
    {
        $this->clearError();

        if (!$this->validateFile($filePath)) {
            return array(
                'success' => false,
                'output'  => '',
                'error'   => $this->getLastError(),
            );
        }

        return $this->runCommand('dcmdump', array($filePath));
    }

    /**
     * Devuelve la salida cruda de dcmdump.
     *
     * @param string $filePath
     * @return string|false
     */
    public function dumpRaw($filePath)
    {
        $result = $this->dump($filePath);
        return $result['success'] ? $result['output'] : false;
    }

    /**
     * Convierte un DICOM a JSON usando dcm2json.
     *
     * @param string $filePath
     * @param bool $decode
     * @return array
     */
    public function toJson($filePath, $decode = true)
    {
        $this->clearError();

        if (!$this->validateFile($filePath)) {
            return array(
                'success' => false,
                'json'    => null,
                'raw'     => '',
                'error'   => $this->getLastError(),
            );
        }

        $result = $this->runCommand('dcm2json', array($filePath));

        if (!$result['success']) {
            return array(
                'success' => false,
                'json'    => null,
                'raw'     => $result['output'],
                'error'   => $result['error'],
            );
        }

        if (!$decode) {
            return array(
                'success' => true,
                'json'    => null,
                'raw'     => $result['output'],
                'error'   => '',
            );
        }

        $decoded = json_decode($result['output'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->setError('No se pudo decodificar el JSON generado por dcm2json: ' . json_last_error_msg());
            return array(
                'success' => false,
                'json'    => null,
                'raw'     => $result['output'],
                'error'   => $this->getLastError(),
            );
        }

        return array(
            'success' => true,
            'json'    => $decoded,
            'raw'     => $result['output'],
            'error'   => '',
        );
    }

    /**
     * Genera una imagen preview desde un DICOM usando dcmj2pnm.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string $format png|jpeg|bmp
     * @return array
     */
    public function createPreview($inputFile, $outputFile, $format = 'png')
    {
        $this->clearError();

        if (!$this->validateFile($inputFile)) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $outputDir = dirname($outputFile);

        if (!is_dir($outputDir)) {
            $this->setError('El directorio de salida no existe: ' . $outputDir);
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        if (!is_writable($outputDir)) {
            $this->setError('El directorio de salida no tiene permisos de escritura: ' . $outputDir);
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $format = strtolower($format);
        $args = array();

        switch ($format) {
            case 'jpg':
            case 'jpeg':
                $args[] = '+oj';
                break;

            case 'bmp':
                $args[] = '+ob';
                break;

            case 'png':
            default:
                $args[] = '+op';
                break;
        }

        $args[] = $inputFile;
        $args[] = $outputFile;

        $result = $this->runCommand('dcmj2pnm', $args);

        if (!$result['success']) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $result['error'],
            );
        }

        if (!file_exists($outputFile)) {
            $this->setError('La imagen de salida no se generó correctamente: ' . $outputFile);
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        return array(
            'success' => true,
            'output_file' => $outputFile,
            'error' => '',
        );
    }

    /**
     * Extrae algunos metadatos habituales desde dcmdump.
     *
     * @param string $filePath
     * @return array
     */
    public function getBasicMetadata($filePath)
    {
        $result = $this->dump($filePath);

        if (!$result['success']) {
            return array(
                'success'  => false,
                'metadata' => array(),
                'error'    => $result['error'],
            );
        }

        $dump = $result['output'];

        $metadata = array(
            'patient_name'         => $this->extractTagValue($dump, '0010,0010'),
            'patient_id'           => $this->extractTagValue($dump, '0010,0020'),
            'study_instance_uid'   => $this->extractTagValue($dump, '0020,000D'),
            'series_instance_uid'  => $this->extractTagValue($dump, '0020,000E'),
            'sop_instance_uid'     => $this->extractTagValue($dump, '0008,0018'),
            'modality'             => $this->extractTagValue($dump, '0008,0060'),
            'study_date'           => $this->extractTagValue($dump, '0008,0020'),
            'study_time'           => $this->extractTagValue($dump, '0008,0030'),
            'accession_number'     => $this->extractTagValue($dump, '0008,0050'),
            'study_description'    => $this->extractTagValue($dump, '0008,1030'),
            'series_description'   => $this->extractTagValue($dump, '0008,103E'),
            'institution_name'     => $this->extractTagValue($dump, '0008,0080'),
            'manufacturer'         => $this->extractTagValue($dump, '0008,0070'),
            'rows'                 => $this->extractTagValue($dump, '0028,0010'),
            'columns'              => $this->extractTagValue($dump, '0028,0011'),
        );

        return array(
            'success'  => true,
            'metadata' => $metadata,
            'error'    => '',
        );
    }

    /**
     * Busca un tag concreto dentro de la salida de dcmdump.
     *
     * @param string $dump
     * @param string $tag
     * @return string|null
     */
    public function extractTagValue($dump, $tag)
    {
        $tag = preg_quote($tag, '/');
        $pattern = '/\(' . $tag . '\)\s+[A-Z]{2}\s+\[[^\]]*\]/';

        if (preg_match($pattern, $dump, $matches)) {
            if (preg_match('/\[(.*?)\]/', $matches[0], $valueMatch)) {
                return trim($valueMatch[1]);
            }
        }

        $patternEmpty = '/\(' . $tag . '\)\s+[A-Z]{2}\s+\(no value available\)/';
        if (preg_match($patternEmpty, $dump)) {
            return '';
        }

        return null;
    }

    /**
     * Comprueba si un archivo parece ser DICOM.
     *
     * @param string $filePath
     * @return bool
     */
    public function isDicom($filePath)
    {
        $result = $this->dump($filePath);
        return !empty($result['success']);
    }
}