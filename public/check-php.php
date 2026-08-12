<?php
header('Content-Type: application/json');

echo json_encode([
    'max_file_uploads' => ini_get('max_file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_input_vars' => ini_get('max_input_vars'),
    'php_version' => PHP_VERSION,
    'loaded_ini_file' => php_ini_loaded_file(),
    'additional_ini_files' => php_ini_scanned_files(),
], JSON_PRETTY_PRINT);
