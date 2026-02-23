<?php
file_put_contents(__DIR__ . '/banned.txt', '');
foreach (glob(__DIR__ . '/logs/*.csv') as $file) {
    unlink($file);
}
echo "System reset completed.";
?>