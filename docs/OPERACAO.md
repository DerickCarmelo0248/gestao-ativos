# Operação e implantação — Gestão de Ativos

## Antes de publicar
1. Revisar o PR e exigir o check **CI aprovada** na proteção da main.
2. Registrar commit atual e commit aprovado. Confirmar janela de manutenção.
3. Fazer backup PostgreSQL com `pg_dump -Fc`; verificar seu índice com `pg_restore --list`.
   Essa verificação não substitui restauração de teste em banco separado.
4. Manter cópia fora do servidor de `.env`, `storage` e configurações IIS, com acesso restrito.
   A APP_KEY original é necessária para recuperar dados criptografados. Nunca versionar esses arquivos.
5. No servidor, executar `powershell -File scripts/ops/preflight.ps1`.
   O script é somente leitura, não mostra credenciais e termina com código 1 se detectar falha.
   Ele verifica o arquivo .env, não a configuração efetiva já armazenada em cache.

## Implantação manual controlada
No PAINELHML, pasta `C:\Apps\GestaoAtivos\sistema`, registrar `git rev-parse HEAD`.
Executar cada etapa separadamente e parar no primeiro erro:

```powershell
git status --short
git pull --ff-only origin main
git rev-parse HEAD
```

Conferir se o commit recebido é o aprovado. Preservar o web.config local.
Se composer.lock mudou, executar Composer com `install --no-dev --prefer-dist --optimize-autoloader`, nunca `update`.
Se houver migrações, revisar seu efeito antes de `php artisan migrate --force`.
Nunca usar migrate:fresh ou db:wipe no servidor. Não gerar outra APP_KEY numa atualização.
Limpar views/rotas conforme a alteração; se necessário, reciclar somente o pool GestaoAtivos.
Não executar iisreset no servidor compartilhado.

## Verificação após publicação
- Conferir `/login`, CSS/JavaScript e autenticação.
- Testar consulta de estoque, histórico e permissões de operador/admin.
- Conferir logs locais sem copiar credenciais para chamados ou PRs.
- Registrar commit, horário, executor e resultado. A aprovação do CI não comprova saúde do IIS.

## Retorno e recuperação
Se falhar, interromper novas gravações e acionar o responsável técnico.
Retornar ao commit anterior somente após verificar compatibilidade com o schema atual.
Não desfazer migrações automaticamente: rollback pode apagar dados.
Restaurar banco apenas mediante plano aprovado, considerando registros após o backup.
Testar restauração em ambiente isolado, documentando duração e contagens.

## Pendências de infraestrutura
A TI deve definir responsável e frequência de backup, retenção, teste de restauração,
HTTPS, monitoramento HTTP, retenção de logs e auditoria de alterações em pastas/IIS.
Estas configurações não são ativadas por este repositório.
