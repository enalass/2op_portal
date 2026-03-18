<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Libreria DICOM basada en DCMTK 3.7.0.
 *
 * Resumen de metodos:
 * - __construct: Inicializa la libreria y permite sobreescribir la ruta DCMTK.
 * - getLastError: Devuelve el ultimo error interno registrado.
 * - clearError: Limpia el estado de error interno.
 * - setError: Registra un error interno y lo escribe en logs.
 * - getBinaryPath: Construye la ruta absoluta de un binario DCMTK.
 * - validateFile: Valida existencia y lectura de un archivo.
 * - validateBinary: Valida existencia y ejecucion de un binario DCMTK.
 * - runCommand: Ejecuta un comando DCMTK con entorno controlado.
 * - ping: Comprueba que dcmdump responde correctamente.
 * - dump: Ejecuta dcmdump sobre un archivo DICOM.
 * - dumpRaw: Devuelve la salida cruda de dcmdump.
 * - toJson: Convierte un DICOM a JSON con dcm2json.
 * - createPreview: Genera preview de imagen desde DICOM con dcmj2pnm.
 * - getBasicMetadata: Extrae metadatos habituales desde dcmdump.
 * - extractTagValue: Obtiene el valor de un tag desde el dump.
 * - isDicom: Determina si un archivo parece DICOM.
 * - validateAbsolutePath: Verifica que una ruta sea absoluta.
 * - validateDirectory: Valida existencia/permisos de un directorio.
 * - validateTagName: Valida formato permitido de nombre de tag DICOM.
 * - applyTagsToDicom: Aplica un conjunto de tags sobre un DICOM.
 * - modifyTag: Modifica o inserta un tag DICOM usando dcmodify.
 * - convertPdfToDicom: Convierte PDF a DICOM con pdf2dcm y aplica metadatos.
 * - createPdfDicom: Crea DICOM PDF con datos de paciente/estudio.
 * - convertJpegToDicom: Convierte JPG/JPEG a DICOM con img2dcm.
 * - convertPngToDicom: Punto de entrada para PNG con error guiado a JPG.
 * - sendToPacs: Envia uno o varios DICOM a PACS con dcmsend.
 * - detectFileType: Detecta tipo util de archivo (dicom, pdf, jpg, png, otro).
 * - listFilesInDirectory: Recorre carpeta y lista archivos con metadatos.
 * - convertPngToJpeg: Convierte PNG a JPG usando ImageMagick.
 */

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
     * Ruta al binario convert de ImageMagick.
     *
     * @var string
     */
    protected $imageMagickConvertPath = '/var/www/vhosts/desarrolloinformatico.com/tools/convert';

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

        if (!empty($params['imagemagick_convert_path'])) {
            $this->imageMagickConvertPath = (string) $params['imagemagick_convert_path'];
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
     * Comprueba si existe un binario externo por ruta absoluta.
     *
     * @param string $binaryPath
     * @param string $name
     * @return bool
     */
    protected function validateAbsoluteBinary($binaryPath, $name = 'binario externo')
    {
        if (!$this->validateAbsolutePath($binaryPath, 'ruta de ' . $name)) {
            return false;
        }

        if (!$this->isPathAllowedByOpenBaseDir($binaryPath)) {
            $allowed = ini_get('open_basedir');
            $this->setError(
                'La ruta del ' . $name . ' no está permitida por open_basedir: ' . $binaryPath .
                '. Rutas permitidas: ' . $allowed .
                '. Configure un path permitido con imagemagick_convert_path.'
            );
            return false;
        }

        if (!file_exists($binaryPath)) {
            $this->setError('No existe el ' . $name . ': ' . $binaryPath);
            return false;
        }

        if (!is_executable($binaryPath)) {
            $this->setError('El ' . $name . ' no es ejecutable: ' . $binaryPath);
            return false;
        }

        return true;
    }

    /**
     * Comprueba si una ruta esta permitida por open_basedir.
     *
     * @param string $path
     * @return bool
     */
    protected function isPathAllowedByOpenBaseDir($path)
    {
        $openBaseDir = (string) ini_get('open_basedir');
        if ($openBaseDir === '') {
            return true;
        }

        $pathNormalized = rtrim(str_replace('\\', '/', $path), '/');
        $allowedPaths = explode(PATH_SEPARATOR, $openBaseDir);

        foreach ($allowedPaths as $allowedPath) {
            $allowedPath = trim($allowedPath);
            if ($allowedPath === '') {
                continue;
            }

            $allowedNormalized = rtrim(str_replace('\\', '/', $allowedPath), '/');
            if ($allowedNormalized === '') {
                continue;
            }

            if ($pathNormalized === $allowedNormalized || strpos($pathNormalized . '/', $allowedNormalized . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ejecuta un comando absoluto no-DCMTK.
     *
     * @param string $binaryPath
     * @param array $args
     * @return array
     */
    protected function runAbsoluteCommand($binaryPath, array $args = array())
    {
        $escapedArgs = array();
        foreach ($args as $arg) {
            $escapedArgs[] = escapeshellarg((string) $arg);
        }

        $command = escapeshellcmd($binaryPath) . ' ' . implode(' ', $escapedArgs) . ' 2>&1';

        $outputLines = array();
        $exitCode = 1;

        exec($command, $outputLines, $exitCode);

        $output = implode("\n", $outputLines);

        if ($exitCode !== 0) {
            $this->setError('Error ejecutando comando externo. Código: ' . $exitCode . '. Salida: ' . $output);
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

    /**
     * Comprueba que la ruta sea absoluta.
     *
     * @param string $path
     * @param string $label
     * @return bool
     */
    protected function validateAbsolutePath($path, $label = 'ruta')
    {
        if (!is_string($path) || trim($path) === '') {
            $this->setError('La ' . $label . ' está vacía.');
            return false;
        }

        if (strpos($path, '/') !== 0) {
            $this->setError('La ' . $label . ' debe ser absoluta: ' . $path);
            return false;
        }

        return true;
    }

    /**
     * Valida que un directorio exista y sea accesible.
     *
     * @param string $dirPath
     * @param bool $mustBeWritable
     * @return bool
     */
    protected function validateDirectory($dirPath, $mustBeWritable = false)
    {
        if (!$this->validateAbsolutePath($dirPath, 'ruta del directorio')) {
            return false;
        }

        if (!is_dir($dirPath)) {
            $this->setError('El directorio no existe: ' . $dirPath);
            return false;
        }

        if (!is_readable($dirPath)) {
            $this->setError('El directorio no tiene permisos de lectura: ' . $dirPath);
            return false;
        }

        if ($mustBeWritable && !is_writable($dirPath)) {
            $this->setError('El directorio no tiene permisos de escritura: ' . $dirPath);
            return false;
        }

        return true;
    }

    /**
     * Valida que una etiqueta DICOM tenga un formato razonable.
     *
     * @param string $tagName
     * @return bool
     */
    protected function validateTagName($tagName)
    {
        if (!is_string($tagName) || trim($tagName) === '') {
            $this->setError('El nombre del tag DICOM está vacío.');
            return false;
        }

        return (bool) preg_match('/^[A-Za-z0-9_,()\-]+$/', $tagName);
    }

    /**
     * Aplica un conjunto de tags en un fichero DICOM.
     *
     * @param string $dicomPath
     * @param array $tags
     * @param bool $noBackup
     * @return array
     */
    protected function applyTagsToDicom($dicomPath, array $tags, $noBackup = true)
    {
        foreach ($tags as $tagName => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $result = $this->modifyTag($dicomPath, $tagName, $value, $noBackup);
            if (!$result['success']) {
                return $result;
            }
        }

        return array(
            'success' => true,
            'output'  => 'Tags aplicados correctamente.',
            'error'   => '',
        );
    }

    /**
     * Modifica un tag de un fichero DICOM usando dcmodify.
     *
     * @param string $filePath
     * @param string $tagName
     * @param string $value
     * @param bool $noBackup
     * @return array
     */
    public function modifyTag($filePath, $tagName, $value, $noBackup = true)
    {
        $this->clearError();

        if (!$this->validateAbsolutePath($filePath, 'ruta del archivo')) {
            return array(
                'success' => false,
                'output'  => '',
                'error'   => $this->getLastError(),
            );
        }

        if (!$this->validateFile($filePath)) {
            return array(
                'success' => false,
                'output'  => '',
                'error'   => $this->getLastError(),
            );
        }

        if (!$this->validateTagName($tagName)) {
            if ($this->getLastError() === '') {
                $this->setError('Formato de tag DICOM no permitido: ' . $tagName);
            }

            return array(
                'success' => false,
                'output'  => '',
                'error'   => $this->getLastError(),
            );
        }

        $args = array();
        if ($noBackup) {
            $args[] = '--no-backup';
        }

        $valueExpression = $tagName . '=' . (string) $value;
        $modifyArgs = $args;
        $modifyArgs[] = '--modify';
        $modifyArgs[] = $valueExpression;
        $modifyArgs[] = $filePath;

        $result = $this->runCommand('dcmodify', $modifyArgs);
        if (!$result['success']) {
            $insertArgs = $args;
            $insertArgs[] = '--insert';
            $insertArgs[] = $valueExpression;
            $insertArgs[] = $filePath;
            $result = $this->runCommand('dcmodify', $insertArgs);
        }

        return array(
            'success' => $result['success'],
            'output'  => $result['output'],
            'error'   => $result['error'],
        );
    }

    /**
     * Convierte un PDF a DICOM (Encapsulated PDF) usando pdf2dcm.
     *
     * @param string $pdfFile
     * @param string $outputDicom
     * @param array $metadata
     * @return array
     */
    public function convertPdfToDicom($pdfFile, $outputDicom, array $metadata = array())
    {
        $this->clearError();

        if (!$this->validateAbsolutePath($pdfFile, 'ruta del PDF') || !$this->validateAbsolutePath($outputDicom, 'ruta del DICOM de salida')) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        if (!$this->validateFile($pdfFile)) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        if ($this->detectFileType($pdfFile) !== 'pdf') {
            $this->setError('El archivo de entrada no es un PDF válido: ' . $pdfFile);
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $outputDir = dirname($outputDicom);
        if (!$this->validateDirectory($outputDir, true)) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $result = $this->runCommand('pdf2dcm', array($pdfFile, $outputDicom));
        if (!$result['success']) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $result['error'],
            );
        }

        if (!file_exists($outputDicom)) {
            $this->setError('No se generó el DICOM de salida: ' . $outputDicom);
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $tags = array(
            'PatientName'      => isset($metadata['patient_name']) ? $metadata['patient_name'] : '',
            'PatientID'        => isset($metadata['patient_id']) ? $metadata['patient_id'] : '',
            'StudyDate'        => isset($metadata['study_date']) ? $metadata['study_date'] : '',
            'StudyTime'        => isset($metadata['study_time']) ? $metadata['study_time'] : '',
            'StudyDescription' => isset($metadata['study_description']) ? $metadata['study_description'] : '',
            'StudyInstanceUID' => isset($metadata['study_instance_uid']) ? $metadata['study_instance_uid'] : '',
        );

        $tagResult = $this->applyTagsToDicom($outputDicom, $tags, true);
        if (!$tagResult['success']) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $tagResult['error'],
            );
        }

        return array(
            'success' => true,
            'output_file' => $outputDicom,
            'error' => '',
        );
    }

    /**
     * Crea un DICOM PDF con metadatos de paciente/estudio.
     *
     * @param string $pdfFile
     * @param string $outputDicom
     * @param string $patientName
     * @param string $patientId
     * @param string $studyDate
     * @param string $studyTime
     * @param string $studyDescription
     * @param string|null $studyInstanceUid
     * @param string|null $sourceDicom
     * @return array
     */
    public function createPdfDicom(
        $pdfFile,
        $outputDicom,
        $patientName,
        $patientId,
        $studyDate,
        $studyTime,
        $studyDescription,
        $studyInstanceUid = null,
        $sourceDicom = null
    ) {
        $this->clearError();

        $metadata = array(
            'patient_name'      => (string) $patientName,
            'patient_id'        => (string) $patientId,
            'study_date'        => (string) $studyDate,
            'study_time'        => (string) $studyTime,
            'study_description' => (string) $studyDescription,
            'study_instance_uid' => '',
        );

        if (!empty($studyInstanceUid)) {
            $metadata['study_instance_uid'] = (string) $studyInstanceUid;
        } elseif (!empty($sourceDicom)) {
            if (!$this->validateAbsolutePath($sourceDicom, 'ruta del DICOM de referencia') || !$this->validateFile($sourceDicom)) {
                return array(
                    'success' => false,
                    'output_file' => '',
                    'error' => $this->getLastError(),
                );
            }

            $basic = $this->getBasicMetadata($sourceDicom);
            if (!$basic['success']) {
                return array(
                    'success' => false,
                    'output_file' => '',
                    'error' => $basic['error'],
                );
            }

            if (!empty($basic['metadata']['study_instance_uid'])) {
                $metadata['study_instance_uid'] = $basic['metadata']['study_instance_uid'];
            }
        }

        return $this->convertPdfToDicom($pdfFile, $outputDicom, $metadata);
    }

    /**
     * Convierte una imagen JPEG/JPG a DICOM usando img2dcm.
     *
     * @param string $imageFile
     * @param string $outputDicom
     * @param array $metadata
     * @return array
     */
    public function convertJpegToDicom($imageFile, $outputDicom, array $metadata = array())
    {
        $this->clearError();

        if (!$this->validateAbsolutePath($imageFile, 'ruta de la imagen') || !$this->validateAbsolutePath($outputDicom, 'ruta del DICOM de salida')) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        if (!$this->validateFile($imageFile)) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $detectedType = $this->detectFileType($imageFile);
        if ($detectedType !== 'jpg' && $detectedType !== 'jpeg') {
            $this->setError('img2dcm solo se ejecuta desde este método para JPG/JPEG. Tipo detectado: ' . $detectedType);
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $outputDir = dirname($outputDicom);
        if (!$this->validateDirectory($outputDir, true)) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $result = $this->runCommand('img2dcm', array($imageFile, $outputDicom));
        if (!$result['success']) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $result['error'],
            );
        }

        if (!file_exists($outputDicom)) {
            $this->setError('No se generó el DICOM de salida: ' . $outputDicom);
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $tags = array(
            'PatientName'      => isset($metadata['patient_name']) ? $metadata['patient_name'] : '',
            'PatientID'        => isset($metadata['patient_id']) ? $metadata['patient_id'] : '',
            'StudyDate'        => isset($metadata['study_date']) ? $metadata['study_date'] : '',
            'StudyTime'        => isset($metadata['study_time']) ? $metadata['study_time'] : '',
            'StudyDescription' => isset($metadata['study_description']) ? $metadata['study_description'] : '',
            'StudyInstanceUID' => isset($metadata['study_instance_uid']) ? $metadata['study_instance_uid'] : '',
        );

        $tagResult = $this->applyTagsToDicom($outputDicom, $tags, true);
        if (!$tagResult['success']) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $tagResult['error'],
            );
        }

        return array(
            'success' => true,
            'output_file' => $outputDicom,
            'error' => '',
        );
    }

    /**
     * Convierte PNG a JPG usando ImageMagick.
     *
     * @param string $pngFile
     * @param string $jpgFile
     * @param int $quality
     * @return array
     */
    public function convertPngToJpeg($pngFile, $jpgFile, $quality = 92)
    {
        $this->clearError();

        if (!$this->validateAbsolutePath($pngFile, 'ruta del PNG') || !$this->validateAbsolutePath($jpgFile, 'ruta del JPG de salida')) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        if (!$this->validateFile($pngFile)) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $detectedType = $this->detectFileType($pngFile);
        if ($detectedType !== 'png') {
            $this->setError('El archivo no es PNG: ' . $pngFile);
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $outputDir = dirname($jpgFile);
        if (!$this->validateDirectory($outputDir, true)) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        if (!$this->validateAbsoluteBinary($this->imageMagickConvertPath, 'binario convert de ImageMagick')) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $quality = (int) $quality;
        if ($quality < 1 || $quality > 100) {
            $quality = 92;
        }

        $result = $this->runAbsoluteCommand($this->imageMagickConvertPath, array(
            $pngFile,
            '-flatten',
            '-background',
            'white',
            '-quality',
            (string) $quality,
            $jpgFile,
        ));

        if (!$result['success']) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $result['error'],
            );
        }

        if (!file_exists($jpgFile)) {
            $this->setError('No se generó el JPG de salida desde PNG: ' . $jpgFile);
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        return array(
            'success' => true,
            'output_file' => $jpgFile,
            'error' => '',
        );
    }

    /**
     * Convierte PNG a DICOM pasando por JPG con ImageMagick + img2dcm.
     *
     * @param string $pngFile
     * @param string $outputDicom
     * @param array $metadata
     * @param string|null $temporaryJpgFile
     * @return array
     */
    public function convertPngToDicom($pngFile, $outputDicom, array $metadata = array(), $temporaryJpgFile = null)
    {
        $this->clearError();

        if (!$this->validateAbsolutePath($outputDicom, 'ruta del DICOM de salida')) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $outputDir = dirname($outputDicom);
        if (!$this->validateDirectory($outputDir, true)) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $autoTemporary = false;
        if (empty($temporaryJpgFile)) {
            $temporaryJpgFile = $outputDir . '/tmp_png2jpg_' . uniqid('', true) . '.jpg';
            $autoTemporary = true;
        } elseif (!$this->validateAbsolutePath($temporaryJpgFile, 'ruta temporal JPG')) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $this->getLastError(),
            );
        }

        $jpgResult = $this->convertPngToJpeg($pngFile, $temporaryJpgFile);
        if (!$jpgResult['success']) {
            return array(
                'success' => false,
                'output_file' => '',
                'error' => $jpgResult['error'],
            );
        }

        $dicomResult = $this->convertJpegToDicom($temporaryJpgFile, $outputDicom, $metadata);

        if ($autoTemporary && file_exists($temporaryJpgFile)) {
            @unlink($temporaryJpgFile);
        }

        return array(
            'success' => $dicomResult['success'],
            'output_file' => $dicomResult['success'] ? $outputDicom : '',
            'error' => $dicomResult['error'],
        );
    }

    /**
     * Envía DICOM a un PACS usando dcmsend.
     *
     * Soporta:
     * - Lista de ficheros (modo fichero)
     * - Ruta de directorio con +sd (modo directorio)
     *
     * @param string $host
     * @param int $port
     * @param string|array $dicomFiles Archivo, array de archivos o directorio
     * @param string $calledAet AET de destino (opcional)
     * @param string $callingAet AET origen (opcional)
     * @param array $extraArgs Argumentos adicionales de dcmsend
     * @return array
     */
    public function sendToPacs($host, $port, $dicomFiles, $calledAet = '', $callingAet = '', array $extraArgs = array())
    {
        $this->clearError();

        $host = trim((string) $host);
        if ($host === '') {
            $this->setError('El host PACS está vacío.');
            return array(
                'success' => false,
                'output' => '',
                'error' => $this->getLastError(),
            );
        }

        $port = (int) $port;
        if ($port <= 0 || $port > 65535) {
            $this->setError('El puerto PACS no es válido: ' . $port);
            return array(
                'success' => false,
                'output' => '',
                'error' => $this->getLastError(),
            );
        }

        $sendDirectory = false;
        $normalizedFiles = array();
        $directoryPath = '';

        if (is_string($dicomFiles) && $dicomFiles !== '' && is_dir($dicomFiles)) {
            if (!$this->validateAbsolutePath($dicomFiles, 'ruta del directorio DICOM') || !$this->validateDirectory($dicomFiles, false)) {
                return array(
                    'success' => false,
                    'output' => '',
                    'error' => $this->getLastError(),
                );
            }

            $sendDirectory = true;
            $directoryPath = $dicomFiles;
        } else {
            $files = is_array($dicomFiles) ? $dicomFiles : array($dicomFiles);
            if (empty($files)) {
                $this->setError('No se indicaron ficheros DICOM para enviar.');
                return array(
                    'success' => false,
                    'output' => '',
                    'error' => $this->getLastError(),
                );
            }

            foreach ($files as $filePath) {
                if (!$this->validateAbsolutePath($filePath, 'ruta del archivo DICOM') || !$this->validateFile($filePath)) {
                    return array(
                        'success' => false,
                        'output' => '',
                        'error' => $this->getLastError(),
                    );
                }

                if (!$this->isDicom($filePath)) {
                    $this->setError('El fichero no parece DICOM y no se enviará: ' . $filePath);
                    return array(
                        'success' => false,
                        'output' => '',
                        'error' => $this->getLastError(),
                    );
                }

                $normalizedFiles[] = $filePath;
            }
        }

        $args = array();

        if (!empty($calledAet)) {
            // --call replica el comando CLI que funciona en este entorno.
            $args[] = '--call';
            $args[] = (string) $calledAet;
        }

        if (!empty($callingAet)) {
            $args[] = '-aet';
            $args[] = (string) $callingAet;
        }

        $args[] = $host;
        $args[] = (string) $port;

        foreach ($extraArgs as $arg) {
            $args[] = (string) $arg;
        }

        if ($sendDirectory) {
            $args[] = '+sd';
            $args[] = $directoryPath;
        } else {
            foreach ($normalizedFiles as $filePath) {
                $args[] = $filePath;
            }
        }

        $result = $this->runCommand('dcmsend', $args);

        return array(
            'success' => $result['success'],
            'output' => $result['output'],
            'error' => $result['error'],
            'directory' => $sendDirectory ? $directoryPath : '',
            'files' => $normalizedFiles,
        );
    }

    /**
     * Detecta el tipo de fichero de forma práctica para flujos DICOM.
     *
     * @param string $filePath
     * @return string dicom|pdf|jpg|jpeg|png|otro
     */
    public function detectFileType($filePath)
    {
        if (!is_string($filePath) || $filePath === '' || !file_exists($filePath) || !is_file($filePath)) {
            return 'otro';
        }

        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return 'pdf';
        }

        if ($extension === 'jpg') {
            return 'jpg';
        }

        if ($extension === 'jpeg') {
            return 'jpeg';
        }

        if ($extension === 'png') {
            return 'png';
        }

        if ($extension === 'dcm') {
            return 'dicom';
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        if ($finfo !== false) {
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            if ($mimeType === 'application/pdf') {
                return 'pdf';
            }

            if ($mimeType === 'image/jpeg') {
                return 'jpg';
            }

            if ($mimeType === 'image/png') {
                return 'png';
            }
        }

        if ($this->isDicom($filePath)) {
            return 'dicom';
        }

        return 'otro';
    }

    /**
     * Recorre una carpeta y lista todos los ficheros detectados.
     *
     * @param string $directory
     * @param bool $recursive
     * @return array
     */
    public function listFilesInDirectory($directory, $recursive = true)
    {
        $this->clearError();

        if (!$this->validateDirectory($directory, false)) {
            return array(
                'success' => false,
                'files' => array(),
                'error' => $this->getLastError(),
            );
        }

        $files = array();

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $filePath = $item->getPathname();
                    $detectedType = $this->detectFileType($filePath);

                    $files[] = array(
                        'path'       => $filePath,
                        'name'       => $item->getFilename(),
                        'extension'  => strtolower((string) pathinfo($item->getFilename(), PATHINFO_EXTENSION)),
                        'size'       => $item->getSize(),
                        'type'       => $detectedType,
                        'is_dicom'   => ($detectedType === 'dicom'),
                    );
                }
            }
        } else {
            $items = scandir($directory);
            foreach ($items as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }

                $filePath = $directory . '/' . $name;
                if (!is_file($filePath)) {
                    continue;
                }

                $detectedType = $this->detectFileType($filePath);
                $files[] = array(
                    'path'       => $filePath,
                    'name'       => $name,
                    'extension'  => strtolower((string) pathinfo($name, PATHINFO_EXTENSION)),
                    'size'       => filesize($filePath),
                    'type'       => $detectedType,
                    'is_dicom'   => ($detectedType === 'dicom'),
                );
            }
        }

        return array(
            'success' => true,
            'files' => $files,
            'error' => '',
        );
    }
}