<?php

$path = __DIR__.'/resources/views/inventories/index.blade.php';
$content = file_get_contents($path);
$start = strpos($content, '<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">');
if ($start === false) {
    fwrite(STDERR, "start not found\n");
    exit(1);
}
$depth = 0;
$pos = $start;
$end = $start;
while ($pos < strlen($content)) {
    $chunk = substr($content, $pos, 6);
    if ($chunk === '</div>') {
        $depth--;
        if ($depth === 0) {
            $end = $pos + 6;
            break;
        }
    } elseif (substr($chunk, 0, 4) === '<div') {
        $depth++;
    }
    $pos++;
}
if ($end === $start) {
    fwrite(STDERR, "end not found\n");
    exit(1);
}
$old = substr($content, $start, $end - $start);
$content = str_replace($old, '', $content);
file_put_contents($path, $content);
echo 'removed '.strlen($old)." bytes\n";
