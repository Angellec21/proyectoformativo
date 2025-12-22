<?php
$p = __DIR__ . '/libs/dompdf/autoload.inc.php';
if (file_exists($p)) {
    require $p;
    echo "autoload ok\n";
} else {
    echo "autoload missing\n";
}
echo 'class exists: ' . (class_exists('Dompdf\\Dompdf') ? 'yes' : 'no') . "\n";
try {
    if (class_exists('Dompdf\\Dompdf')) {
        $d = new \Dompdf\Dompdf();
        $d->loadHtml('<p>test</p>');
        $d->setPaper('A4', 'portrait');
        $d->render();
        echo "render ok\n";
    }
} catch (Exception $e) {
    echo 'exception: ' . $e->getMessage() . "\n";
}
