# Pipeline de segurança e qualidade

A configuração está em `.github/workflows/ci.yml`. Executa em PRs, push na main,
manualmente e às segundas às 09:23 UTC. O agendamento só começa após o workflow
estar na branch padrão. Nenhum segredo de produção é necessário.

## Verificações

- PHP 8.5, PostgreSQL 18 temporário: validação do Composer, audit de todas as
  dependências PHP do lock, extensões, sintaxe, migrações, Blade e testes.
- Node 24: `npm ci` usando package-lock, audit (moderate/high/critical) e build.
- Gitleaks: varredura do histórico completo, com valores de segredos ocultados.
- Semgrep: quatro regras locais para eval, unserialize, comandos de shell e
  mass assignment com request->all(). É uma análise inicial, não cobre todas
  as vulnerabilidades nem substitui revisão humana ou testes de autorização.
- Arquivos versionados: rejeita .env real, auth.json, chaves e backups.
- Dependabot: propostas semanais para Composer, npm e Actions. Não há auto-merge.

O job `CI aprovada` falha se qualquer verificação falhar ou for pulada/cancelada.
Não há deploy, runner interno, credenciais da empresa ou acesso ao banco real.
Os dados de login do PostgreSQL no YAML são apenas do container descartável.
O `POSTGRES_USER` do container é administrador somente desse banco efêmero;
isso não testa os privilégios restritos que serão usados em produção.

## Ativar no GitHub

1. Enviar estes arquivos e o package-lock.json em uma branch e abrir um PR.
2. Acompanhar Actions e corrigir falhas. Não desativar audits para ficar verde.
3. Em Settings > Rules > Rulesets (ou Branches), criar proteção para main:
   - exigir Pull Request;
   - exigir o status **CI aprovada**, selecionando GitHub Actions como origem;
   - exigir branch atualizada antes do merge;
   - bloquear force push e exclusão da branch;
   - aplicar a administradores e restringir bypass;
   - exigir resolução das conversas.
4. Com outro revisor na equipe, exigir uma aprovação e revisão de CODEOWNERS.
   O autor não aprova o próprio PR; não exigir aprovação de si mesmo quando
   houver apenas um colaborador.
5. Em Settings > Actions > General, manter token com acesso de leitura e não
   permitir que Actions criem/aprovem PRs. Habilitar alertas de dependências e
   proteção de segredos, quando disponíveis no plano do repositório.

**Os arquivos não ativam a proteção de branch por si só.** Sem a regra, um push
direto na main pode entrar antes de a pipeline apontar o problema. Algumas
proteções dependem do plano/visibilidade do repositório. Confirmar no GitHub.

## Como trabalhar

Criar uma branch, editar, testar, commitar, enviar e abrir PR para main.
Revisar os resultados antes do merge. Para vazamento real, revogar/rotacionar
a credencial primeiro; removê-la somente do último commit não limpa o histórico.
Falsos positivos exigem revisão e exceção específica, nunca ignorar tudo.

Atualizar periodicamente as versões de Semgrep e Gitleaks no workflow e validar
novamente as regras. As Actions estão fixadas por SHA; Dependabot propõe atualizações.
Manter patches do PHP, PostgreSQL e ferramentas atualizados no servidor também.

## Limites e validação

CI Linux não substitui homologação no IIS/Windows. Os testes ainda precisam
evoluir para cobrir concorrência, todo o descarte e a configuração de produção.
Pipeline verde não é garantia de ausência de vulnerabilidades. Manter backups,
restauração testada e revisão antes de publicar no servidor.

Comandos locais: `php artisan test`, `composer validate --strict`,
`composer audit --locked`, `npm ci --ignore-scripts`, `npm audit --audit-level=moderate`,
`npm run build`. Dependências de PDF/Excel ainda podem exigir correção do TLS
corporativo para serem instaladas localmente; nunca usar SSL desabilitado.

Referências oficiais:
- https://docs.github.com/en/actions/reference/security/secure-use
- https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches
- https://github.com/gitleaks/gitleaks
- https://semgrep.dev/docs/running-rules
