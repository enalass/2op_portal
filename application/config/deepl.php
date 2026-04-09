<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['deepl'] = array(
    // Habilita/deshabilita el cliente DeepL.
    'enabled' => true,

    // API key DeepL.
    'api_key' => 'PON_AQUI_TU_DEEPL_API_KEY',

    // Usa api-free para planes Free y api.deepl.com para planes Pro.
    'api_base_url' => 'https://api-free.deepl.com/v2',

    // Idiomas por defecto.
    'default_source_lang' => 'ES',
    'default_target_lang' => 'EN-GB',

    // Opciones de traduccion.
    'formality' => 'prefer_more',
    'preserve_formatting' => 1,
    'split_sentences' => '1',
    'model_type' => 'prefer_quality_optimized',

    // Opciones opcionales avanzadas.
    'glossary_id' => '',
    'tag_handling' => '',

    // Conexion HTTP.
    'timeout' => 20,
    'verify_ssl' => true,

    // Contexto por defecto para reforzar tono medico.
    'medical_context' => 'Medical radiology report. Use precise clinical terminology, keep findings and conclusions faithful, and avoid simplifying diagnostic meaning.',
);
