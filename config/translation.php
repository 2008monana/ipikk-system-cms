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

if (!defined('LIBRETRANSLATE_URL')) {
    define('LIBRETRANSLATE_URL', getenv('LIBRETRANSLATE_URL') ?: 'http://127.0.0.1:5000/translate');
}

if (!defined('LIBRETRANSLATE_API_KEY')) {
    define('LIBRETRANSLATE_API_KEY', getenv('LIBRETRANSLATE_API_KEY') ?: '');
}
