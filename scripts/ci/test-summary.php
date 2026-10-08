<?php

$path = 'storage/logs/junit.xml';
$destination = getenv('GITHUB_STEP_SUMMARY');
if (! $destination) {
    exit(0);
}
if (! is_file($path)) {
    file_put_contents($destination, "## Testes PHP\nRelatório não gerado; consulte a etapa que falhou.\n", FILE_APPEND);
    exit(0);
}
$xml = simplexml_load_file($path, SimpleXMLElement::class, LIBXML_NONET);
if ($xml === false) {
    exit(1);
}
$tests = count($xml->xpath('//testcase'));
$failures = count($xml->xpath('//testcase[failure or error]'));
$skipped = count($xml->xpath('//testcase[skipped]'));
file_put_contents($destination, "## Testes PHP\n- Casos: {$tests}\n- Falhas/erros: {$failures}\n- Ignorados: {$skipped}\n", FILE_APPEND);
