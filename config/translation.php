<?php
/**
 * Configuração de tradução.
 */

if (!defined('TRANSLATION_PROVIDER')) {
    define('TRANSLATION_PROVIDER', getenv('TRANSLATION_PROVIDER') ?: 'google');
}

if (!defined('GOOGLE_TRANSLATE_API_KEY')) {
    define('GOOGLE_TRANSLATE_API_KEY', getenv('GOOGLE_CLOUD_TRANSLATE_API_KEY') ?: (getenv('GOOGLE_TRANSLATE_API_KEY') ?: ''));
}

if (!defined('TRANSLATION_ENABLE_FALLBACK')) {
    define('TRANSLATION_ENABLE_FALLBACK', (getenv('TRANSLATION_ENABLE_FALLBACK') ?: '0') === '1');
}
