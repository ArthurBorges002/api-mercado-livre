# Como contribuir

Obrigado pelo interesse em melhorar a **api-mercado-livre**. Este guia explica como relatar problemas, propor mudanças e enviar código.

## Antes de começar

- Procure nas [issues](https://github.com/ArthurBorges002/api-mercado-livre/issues) se o assunto já foi aberto.
- Para mudanças grandes (um recurso novo da API, troca de comportamento), abra uma issue antes e combine a ideia. Isso evita trabalho perdido.
- Correções pequenas (erro de digitação, documentação, bug óbvio) podem ir direto em um pull request.

## Relatar um bug

Abra uma issue com:

1. O que você fez (trecho de código mínimo que reproduz o problema).
2. O que esperava e o que aconteceu (mensagem de erro, status HTTP, `ApiException` ou `AuthException`).
3. Versão do PHP e da biblioteca (commit ou tag).
4. O endpoint do Mercado Livre envolvido, se souber (ex.: `/orders/search`).

**Nunca publique credenciais.** Antes de colar logs ou respostas da API, apague `client_secret`, `access_token`, `refresh_token` e dados pessoais de compradores (nome, CPF/CNPJ, endereço, telefone).

## Falhas de segurança

Não abra issue pública para falhas de segurança (vazamento de token, validação de webhook, etc.). Use o relato privado do GitHub: aba **Security**, botão **Report a vulnerability**.

## Ambiente de desenvolvimento

Requisitos:

- PHP **7.4 ou superior**, com as extensões `curl` e `json`
- Git

A biblioteca não tem dependências externas, então não é preciso rodar `composer install`.

```bash
git clone https://github.com/ArthurBorges002/api-mercado-livre.git
cd api-mercado-livre
php tests/teste_offline.php
```

### Testes

`tests/teste_offline.php` simula as respostas do Mercado Livre e roda sem internet e sem credenciais. Todo pull request deve:

- passar nesse teste no **PHP 7.4** e no **PHP 8**;
- incluir testes novos para o comportamento que adiciona ou corrige.

### Testar contra a API real (opcional)

Os scripts em `examples/` usam um aplicativo de verdade. Crie um app de teste em [developers.mercadolivre.com.br](https://developers.mercadolivre.com.br), use [usuários de teste](https://developers.mercadolivre.com.br/pt_br/realizacao-de-testes) e coloque as credenciais em `examples/_env.php`. Esse arquivo está no `.gitignore` e nunca deve ser commitado.

Não use contas reais de vendedor em testes.

## Padrões de código

- **Compatível com PHP 7.4.** Não use recursos só do PHP 8 (`match`, promoção de propriedades no construtor, argumentos nomeados, union types, `enum`, `readonly`).
- `declare(strict_types=1);` no início de cada arquivo.
- Namespace `ApiMercadoLivre\` dentro de `src/` (PSR-4); uma classe por arquivo.
- Estilo [PSR-12](https://www.php-fig.org/psr/psr-12/).
- Sem dependências externas. Se uma mudança precisar de pacote novo, discuta antes na issue.
- A biblioteca não conhece o banco de quem a usa. Persistência fica atrás de interfaces como `TokenStorage`.
- Erros da API viram exceções da biblioteca (`ApiException`, `AuthException`), não `false` ou `null` silenciosos.
- Comentários, mensagens de erro e documentação em português.
- Arquivos em UTF-8.

## Enviar um pull request

1. Faça um fork e crie uma branch a partir da `main` com nome curto e descritivo (ex.: `pedidos-pagos-desde`, `modelo-envio`).
2. Faça commits pequenos, com mensagem em português no imperativo ou descritiva (ex.: `Modelo Shipment: endereço de entrega nos formatos novo e antigo`).
3. Rode `php tests/teste_offline.php` antes de enviar.
4. Atualize o `README.md` se a mudança altera o uso público da biblioteca.
5. Abra o pull request para a `main` explicando:
    - o que muda e por quê;
    - como testou (offline e, se houver, contra a API com usuários de teste);
    - se quebra compatibilidade com quem já usa a biblioteca.

O pull request é revisado antes do merge. Pode haver pedidos de ajuste.

## Licença

Ao contribuir, você concorda que seu código será distribuído sob a [licença MIT](LICENSE) do projeto.
