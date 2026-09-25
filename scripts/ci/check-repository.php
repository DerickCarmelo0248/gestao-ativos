<?php

// Inspeciona apenas nomes de arquivos versionados, sem imprimir segredos.
exec('git ls-files -z', $output, $exitCode);
if ($exitCode !== 0) {
    fwrite(STDERR, "Nao foi possivel consultar os arquivos versionados.\n");
    exit(1);
}
$files = explode("\0", implode("\n", $output));
$forbidden = [];
foreach ($files as $file) {
    $base = basename($file);
    if (($base === '.env' || str_starts_with($base, '.env.')) && $base !== '.env.example') {
        $forbidden[] = $file;
    } elseif (preg_match('~(^|/)(auth\.json|id_rsa|id_ed25519)$|\.(pfx|p12|key)$|^(backups|vendor|node_modules)/~i', $file)) {
        $forbidden[] = $file;
    }
}
if ($forbidden !== []) {
    fwrite(STDERR, "Arquivos que nao devem estar versionados:\n".implode("\n", $forbidden)."\n");
    exit(1);
}
echo "Nenhum arquivo de ambiente, chave privada ou backup proibido versionado.\n";
