import { readdirSync } from 'node:fs';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';

function check(directory) {
    for (const entry of readdirSync(directory, { withFileTypes: true })) {
        const path = join(directory, entry.name);
        if (entry.isDirectory()) check(path);
        else if (/\.(js|mjs|cjs)$/.test(entry.name)) {
            const result = spawnSync(process.execPath, ['--check', path], { stdio: 'inherit' });
            if (result.error || result.status !== 0) process.exit(1);
            console.log(`Sintaxe válida: ${path}`);
        }
    }
}
check('public/js');
check('resources/js');
