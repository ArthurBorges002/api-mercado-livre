# Política de segurança

## Versões com suporte

A biblioteca ainda não tem versões publicadas (tags). Correções de segurança entram só na branch `main`. Use sempre o último commit da `main`.

| Versão | Suporte |
| --- | --- |
| `main` (último commit) | Sim |
| Commits anteriores | Não |

## Como relatar uma vulnerabilidade

**Não abra issue, pull request ou discussão pública** sobre falhas de segurança.

Use o relato privado do GitHub:

1. Acesse a aba [Security](https://github.com/ArthurBorges002/api-mercado-livre/security) do repositório.
2. Clique em **Report a vulnerability**.
3. Descreva o problema.

Ou vá direto para [este formulário](https://github.com/ArthurBorges002/api-mercado-livre/security/advisories/new).

Inclua no relato:

- o tipo de falha (ex.: vazamento de token, webhook aceito sem validação, verificação TLS desligada);
- os arquivos ou classes envolvidos (ex.: `src/Http/CurlHttpClient.php`);
- os passos para reproduzir e, se possível, uma prova de conceito;
- o impacto: o que um atacante consegue fazer.

**Não envie credenciais reais.** Troque `client_secret`, `access_token`, `refresh_token` e dados de compradores por valores fictícios.

## O que esperar

O projeto é mantido por uma pessoa, então os prazos são de melhor esforço:

- confirmação de recebimento em até 7 dias;
- avaliação inicial e retorno sobre a gravidade em até 14 dias;
- correção e publicação de um aviso de segurança (GitHub Security Advisory) assim que possível, conforme a gravidade.

Pedimos que a falha não seja divulgada até a correção estar na `main`. Quem relatar será creditado no aviso, se quiser.

## Escopo

**Dentro do escopo:** o código deste repositório (`src/`, `autoload.php`, `examples/`), por exemplo:

- tokens ou a Secret Key expostos em logs, exceções ou arquivos;
- falhas na renovação ou no armazenamento de tokens (`TokenStorage`, `FileTokenStorage`);
- validação insuficiente das notificações do webhook (`Notification`);
- requisições HTTPS sem verificação de certificado.

**Fora do escopo:**

- falhas na API ou na plataforma do Mercado Livre: relate ao próprio Mercado Livre;
- falhas no sistema (ERP) que usa a biblioteca, como o banco onde ele grava os tokens;
- configuração do servidor de quem usa a biblioteca (HTTPS, firewall, permissões de arquivo).

## Boas práticas para quem usa a biblioteca

- Guarde a Secret Key e os tokens fora do Git (variáveis de ambiente ou banco).
- Use HTTPS na Redirect URI e na URL de notificações, como o Mercado Livre exige.
- No webhook, aceite só avisos da sua conta e do seu app: `Notification::belongsTo($sellerId, $applicationId)`.
- Não trate o aviso do webhook como dado confiável: busque o pedido na API antes de gravar.
- Não desligue a verificação TLS. Se o PHP não achar os certificados, passe um arquivo de CA ao `CurlHttpClient`.
- Se usar `FileTokenStorage`, grave o arquivo fora da pasta pública do site e com permissão só para o usuário do PHP.
