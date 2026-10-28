<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Фразы из lang/<LANGUAGE_ID>/config.php ядро подключает само (Extension::getConfig).
return [
    'js' => 'script.js',
    'css' => 'style.css',
    'rel' => ['main.core'],
    'skip_core' => false,
];
