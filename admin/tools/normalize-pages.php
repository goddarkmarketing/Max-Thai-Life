<?php
declare(strict_types=1);
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/landing-pages.php';
require __DIR__ . '/../includes/generate-js.php';
$data = json_read('pages.json');
$data = admin_normalize_pages_data($data);
json_write('pages.json', $data);
generate_all_js();
echo "done\n";
