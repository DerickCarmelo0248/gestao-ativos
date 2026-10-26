@if (session('email_draft'))
    @php($draft = session('email_draft'))
    <details class="email-draft" style="margin: 16px 0;">
        <summary class="button">{{ $draft['label'] }}</summary>
        <p>Revise os dados e preencha os campos indicados antes de enviar. Este texto não envia e-mails nem confirma a emissão da nota ou a baixa patrimonial.</p>
        <label for="email-subject">Assunto</label>
        <input id="email-subject" type="text" value="{{ $draft['subject'] }}" style="width:100%">
        <label for="email-body">Mensagem</label>
        <textarea id="email-body" rows="18" style="width:100%">{{ $draft['body'] }}</textarea>
        <button type="button" id="copy-email">Copiar mensagem</button>
        <span id="copy-email-status" role="status"></span>
    </details>
    <script>
        document.getElementById('copy-email').addEventListener('click', async () => {
            const field = document.getElementById('email-body');
            const status = document.getElementById('copy-email-status');
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(field.value);
                } else {
                    field.focus();
                    field.select();
                    if (!document.execCommand('copy')) throw new Error('copy');
                }
                status.textContent = 'Mensagem copiada. Copie também o assunto acima.';
            } catch {
                field.focus();
                field.select();
                status.textContent = 'Pressione Ctrl+C para copiar a mensagem selecionada.';
            }
        });
    </script>
@endif
