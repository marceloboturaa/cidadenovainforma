# Sistema de Gerenciamento de Consentimento LGPD

## Instalação

1. Atualize o código no servidor.
2. Em instalações existentes, execute `database/update_lgpd_consent.sql` no banco.
3. Acesse `/admin/consent` com usuário `master` ou `admin`.

O módulo também cria as tabelas e permissões automaticamente no primeiro carregamento, mas o SQL é recomendado para implantação controlada.

## Perfis

- `master`: acesso total.
- `admin`: gerencia textos, categorias, scripts, registros e exportação.
- `editor-lgpd`: edita textos do banner e política.
- `visualizador-lgpd`: consulta painel, histórico e relatórios.

## Uso

- Banner público: aparece até o visitante aceitar, rejeitar ou personalizar.
- Política pública: disponível em `/politica-de-cookies`.
- Preferências: o botão “Privacidade” permite alterar ou revogar consentimento.
- Scripts opcionais: cadastre no painel por categoria. Eles só são injetados depois do consentimento correspondente.
- Relatório: use “Exportar consentimentos” no painel.

## Regras LGPD Aplicadas

- Cookies necessários ficam sempre ativos.
- Categorias opcionais não carregam scripts antes do aceite.
- “Aceitar tudo” e “Rejeitar tudo” têm ações explícitas.
- O visitante pode alterar preferências depois.
- O registro salva visitante, usuário logado quando existir, IP anonimizado, navegador, data, versão da política, preferências e origem.

## Cadastrando Scripts

Use categoria `Análise` para Google Analytics/Hotjar, `Marketing` para Meta Pixel e campanhas, e `Preferências` para scripts de experiência. Para URL externa, escolha “URL externa” e informe `src`. Para snippets, escolha “Inline” e cole apenas o conteúdo JavaScript.
