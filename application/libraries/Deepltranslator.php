<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Deepltranslator {

    /** @var CI_Controller */
    protected $ci;

    /** @var array */
    protected $config = array();

    public function __construct($params = array()){
        $this->ci =& get_instance();
        $this->ci->config->load('deepl', TRUE);

        $baseConfig = $this->ci->config->item('deepl');
        if(!is_array($baseConfig)){
            $baseConfig = array();
        }

        $this->config = array_merge($baseConfig, is_array($params) ? $params : array());
    }

    /**
     * Traduccion generica.
     */
    public function translateText($text, $targetLang = '', $sourceLang = '', $options = array()){
        $text = (string)$text;
        if(trim($text) === ''){
            return array('success' => false, 'error' => 'Texto vacio', 'translated_text' => '');
        }

        if(empty($this->config['enabled'])){
            return array('success' => false, 'error' => 'DeepL deshabilitado en configuracion', 'translated_text' => '');
        }

        $apiKey = isset($this->config['api_key']) ? trim((string)$this->config['api_key']) : '';
        if($apiKey === ''){
            return array('success' => false, 'error' => 'Falta deepl.api_key', 'translated_text' => '');
        }

        $target = strtoupper(trim((string)$targetLang));
        if($target === ''){
            $target = isset($this->config['default_target_lang']) ? strtoupper((string)$this->config['default_target_lang']) : 'EN-GB';
        }

        $source = strtoupper(trim((string)$sourceLang));
        if($source === '' && isset($this->config['default_source_lang'])){
            $source = strtoupper((string)$this->config['default_source_lang']);
        }

        $request = array(
            'text' => array($text),
            'target_lang' => $target,
            'formality' => isset($this->config['formality']) ? (string)$this->config['formality'] : 'prefer_more',
            'preserve_formatting' => isset($this->config['preserve_formatting']) ? (int)$this->config['preserve_formatting'] : 1,
            'split_sentences' => isset($this->config['split_sentences']) ? (string)$this->config['split_sentences'] : '1',
            'model_type' => isset($this->config['model_type']) ? (string)$this->config['model_type'] : 'prefer_quality_optimized',
        );

        if($source !== ''){
            $request['source_lang'] = $source;
        }

        $opt = is_array($options) ? $options : array();
        $context = isset($opt['context']) ? trim((string)$opt['context']) : '';
        if($context === '' && isset($this->config['medical_context'])){
            $context = trim((string)$this->config['medical_context']);
        }
        if($context !== ''){
            $request['context'] = $context;
        }

        $glossaryId = isset($opt['glossary_id']) ? trim((string)$opt['glossary_id']) : '';
        if($glossaryId === '' && isset($this->config['glossary_id'])){
            $glossaryId = trim((string)$this->config['glossary_id']);
        }
        if($glossaryId !== ''){
            $request['glossary_id'] = $glossaryId;
        }

        $tagHandling = isset($opt['tag_handling']) ? trim((string)$opt['tag_handling']) : '';
        if($tagHandling === '' && isset($this->config['tag_handling'])){
            $tagHandling = trim((string)$this->config['tag_handling']);
        }
        if($tagHandling !== ''){
            $request['tag_handling'] = $tagHandling;
        }

        $baseUrl = isset($this->config['api_base_url']) ? rtrim((string)$this->config['api_base_url'], '/') : 'https://api-free.deepl.com/v2';
        $url = $baseUrl . '/translate';

        $http = $this->httpPostJson($url, $request, $apiKey);
        if(!$http['success']){
            return array('success' => false, 'error' => $http['error'], 'translated_text' => '');
        }

        $decoded = json_decode($http['body'], true);
        if(!is_array($decoded) || !isset($decoded['translations'][0]['text'])){
            return array('success' => false, 'error' => 'Respuesta DeepL invalida', 'translated_text' => '');
        }

        $translation = $decoded['translations'][0];

        return array(
            'success' => true,
            'translated_text' => (string)$translation['text'],
            'detected_source_lang' => isset($translation['detected_source_language']) ? (string)$translation['detected_source_language'] : '',
            'raw' => $decoded,
        );
    }

    /**
     * Traduccion orientada a informes medicos.
     */
    public function translateMedicalReport($reportText, $targetLang = '', $sourceLang = '', $options = array()){
        $options = is_array($options) ? $options : array();
        if(!isset($options['context']) || trim((string)$options['context']) === ''){
            $options['context'] = isset($this->config['medical_context']) ? (string)$this->config['medical_context'] : '';
        }

        return $this->translateText($reportText, $targetLang, $sourceLang, $options);
    }

    /**
     * Traduce lotes (por ejemplo informe + cabeceras).
     */
    public function translateBatch($texts, $targetLang = '', $sourceLang = '', $options = array()){
        if(!is_array($texts) || empty($texts)){
            return array('success' => false, 'error' => 'Lote vacio', 'translations' => array());
        }

        $results = array();
        foreach($texts as $idx => $item){
            $res = $this->translateText((string)$item, $targetLang, $sourceLang, $options);
            if(!$res['success']){
                return array('success' => false, 'error' => 'Error en item ' . $idx . ': ' . $res['error'], 'translations' => $results);
            }
            $results[] = $res['translated_text'];
        }

        return array('success' => true, 'translations' => $results);
    }

    protected function httpPostJson($url, $payload, $apiKey){
        $ch = curl_init();
        if($ch === false){
            return array('success' => false, 'error' => 'No se pudo inicializar cURL', 'body' => '');
        }

        $headers = array(
            'Authorization: DeepL-Auth-Key ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        );

        $verifySsl = isset($this->config['verify_ssl']) ? (bool)$this->config['verify_ssl'] : true;
        $timeout = isset($this->config['timeout']) ? (int)$this->config['timeout'] : 20;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout > 0 ? $timeout : 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl ? 1 : 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);

        $body = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($body === false){
            return array('success' => false, 'error' => 'Error de red DeepL: ' . $curlErr, 'body' => '');
        }

        if($httpCode < 200 || $httpCode >= 300){
            $errorText = 'DeepL HTTP ' . $httpCode;
            $decoded = json_decode($body, true);
            if(is_array($decoded) && isset($decoded['message'])){
                $errorText .= ' - ' . (string)$decoded['message'];
            }
            return array('success' => false, 'error' => $errorText, 'body' => (string)$body);
        }

        return array('success' => true, 'error' => '', 'body' => (string)$body);
    }
}
