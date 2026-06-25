---
title: "Brand Book / Design System"
status: draft
owner: filipe-magarotto
last_updated: 2026-06-23
ai_friendly: true
tags: [design, brand-book, design-system, tailwind, ui]
---

# Brand Book / Design System

> Padrões de interface do sistema. Objetivo: uma UI **consistente e intuitiva**.
> Implementação em **Tailwind CSS**. Escrito sob a ótica da
> [persona de Front-end & UX](./persona-frontend-ux.md).

## Princípios

- **Consistência:** mesmos componentes, espaçamentos e padrões em todas as telas.
- **Uma ação primária por tela**, sempre óbvia.
- **Acessível por padrão:** contraste, foco visível, navegação por teclado.
- **Mobile-first:** desenhar do menor breakpoint para cima.
- **Sem valores mágicos:** usar a escala do Tailwind, não `p-[13px]`.

## Grid de espaçamento (base 4px)

Usar a escala padrão do Tailwind (1 = 4px). Valores recomendados:

| Token | px | Uso |
|-------|----|-----|
| `1` | 4 | espaçamento mínimo entre ícone e texto |
| `2` | 8 | gap interno de componentes pequenos |
| `3` | 12 | padding de inputs/botões |
| `4` | 16 | padding padrão de cards, gap entre campos |
| `6` | 24 | gap entre grupos/seções pequenas |
| `8` | 32 | espaçamento entre seções |
| `12` | 48 | respiro de blocos maiores |
| `16` | 64 | margens de topo/rodapé de página |

Regra: prefira **múltiplos de 4**; evite empilhar margens — controle espaçamento
com `gap`/`space-y` no container.

## Layout e posicionamento

- **Container de conteúdo:** `max-w-7xl mx-auto`, padding lateral
  `px-4 md:px-6 lg:px-8`.
- **App shell:** header no topo + (opcional) sidebar de navegação à esquerda +
  área de conteúdo. Conteúdo respira com `py-6 md:py-8`.
- **Cabeçalho de página:** título à esquerda; **ação primária no canto superior
  direito**. Ações secundárias à esquerda da primária.
- **Formulários:** uma coluna (`max-w-2xl`), campos empilhados com `space-y-4`;
  botões de submit alinhados à direita ou full-width no mobile.
- **Listas/tabelas:** densidade confortável (linha ~`h-12`), cabeçalho fixo quando
  longo, ação por linha à direita.
- **Responsivo:** breakpoints do Tailwind (`sm 640 · md 768 · lg 1024 · xl 1280`).
  Sidebar vira drawer no mobile.

## Tipografia

Fonte: **sans-serif** (família a definir — ver "Decisões em aberto"). Escala:

| Nível | Classe Tailwind | Peso |
|-------|------------------|------|
| Título de página (H1) | `text-2xl` / `text-3xl` | `font-semibold` |
| Seção (H2) | `text-xl` | `font-semibold` |
| Subseção (H3) | `text-lg` | `font-medium` |
| Corpo | `text-base` (`text-sm` em UI densa) | `font-normal` |
| Auxiliar/legenda | `text-sm` / `text-xs` | `font-normal` |

- **Cor de texto:** corpo `text-neutral-800`; secundário/auxiliar `text-neutral-500`;
  títulos `text-neutral-900`; texto desabilitado `text-neutral-400`.
- `leading-normal`/`leading-relaxed` para blocos de texto; títulos `leading-tight`.

## Botões e ações

**Tamanhos** (altura consistente com inputs):

| Tamanho | Classes | Uso |
|---------|---------|-----|
| `sm` | `h-8 px-3 text-sm` | ações em linhas de tabela, barras densas |
| `md` (padrão) | `h-10 px-4 text-sm` | uso geral |
| `lg` | `h-12 px-6 text-base` | CTAs em telas amplas/mobile |

- **Forma:** `rounded-md`, `font-medium`, `inline-flex items-center gap-2`
  (ícone + texto), transição em hover.
- **Estados (obrigatórios):** default, `hover`, `focus-visible` (anel de foco —
  ver Acessibilidade), `active`, `disabled` (`opacity-50 cursor-not-allowed`),
  **loading** (spinner + desabilitado).

**Variantes** (estrutura padronizada; cor segue a regra de theming):

| Variante | Quando usar | Cor |
|----------|-------------|-----|
| **Primária** | a ação principal da tela | **token de destaque do tenant** (não fixar aqui — ver theming) |
| **Secundária** | ação alternativa | neutra: `border border-neutral-300 text-neutral-800 bg-transparent` |
| **Ghost/terciária** | ações de baixo peso | `text-neutral-700 hover:bg-neutral-100` |
| **Destrutiva** | excluir/remover (irreversível) | semântica vermelha (`red-600`/`red-700`) |
| **Sucesso** | confirmar/concluir (quando semântico) | semântica verde (`green-600`) |

> A cor da variante **primária** vem do tenant (destaque) e **não** é definida
> aqui. As variantes neutra/destrutiva/sucesso são padronizadas.

## Inputs e formulários

- **Altura** alinhada aos botões (`h-10`), `px-3`, `rounded-md`,
  `border border-neutral-300`, fundo `bg-white`.
- **Label** sempre presente (`text-sm font-medium text-neutral-700`), acima do
  campo, `mb-1`.
- **Foco:** `focus:border-...` + anel de foco (ver Acessibilidade).
- **Erro:** borda `border-red-500`, mensagem `text-sm text-red-600` abaixo do campo.
- **Ajuda:** `text-sm text-neutral-500`.
- **Desabilitado:** `bg-neutral-100 text-neutral-400`.

## Bordas, raio e elevação

- **Borda:** `border-neutral-200` (divisores/cards), `border-neutral-300` (inputs).
- **Raio:** `rounded-md` (padrão de botões/inputs/cards), `rounded-lg` (modais/
  containers grandes), `rounded-full` (avatares/badges/pills).
- **Sombra:** `shadow-sm` (cards/elevação sutil), `shadow-md` (dropdowns/popovers),
  `shadow-lg` (modais). Usar elevação com parcimônia.

## Feedback e estados de tela

- **Alertas/toasts (cores semânticas, não de marca):** info `blue`, sucesso
  `green`, atenção `amber`, erro `red`. Ícone + título curto + descrição.
- **Loading:** spinner em botões; **skeletons** para listas/cards; evitar layout
  shift.
- **Estado vazio:** ícone + frase curta + ação para sair do vazio (ex.: "Nenhum
  registro ainda — criar o primeiro").
- **Confirmação destrutiva:** ações irreversíveis pedem confirmação (modal/diálogo).

## Ícones

- Conjunto único e consistente (sugestão: **Heroicons**, que casa com Tailwind —
  a confirmar). Tamanhos `w-4 h-4` (em texto/botões `sm`) e `w-5 h-5` (padrão).
- Ícone nunca sozinho como única label de uma ação sem `aria-label`.

## Acessibilidade

- **Foco visível** em tudo que é interativo: `focus-visible:outline-none
  focus-visible:ring-2 focus-visible:ring-offset-2` (cor do anel = token de
  destaque do tenant). Nunca remover foco sem substituto.
- **Contraste** mínimo AA (texto normal ≥ 4.5:1).
- **Alvo de toque** ≥ 40–44px de altura (por isso `h-10`/`h-11` em controles).
- **Teclado:** tudo operável por Tab/Enter/Esc; ordem de foco lógica.
- **Semântica:** HTML correto, `label` em inputs, `aria-*` quando necessário.

## Cores e theming por tenant

> **Importante:** a **paleta de marca é custom por tenant** e **não** é definida
> neste brand book.

- **Decidido pelo tenant (NÃO padronizar aqui):** cor do **header**, cor de
  **itens em destaque** (ação primária/acento) e **fundo das páginas**.
- **Padronizado aqui (neutro/semântico):** cor de **texto**, **bordas**, variantes
  **neutra/destrutiva/sucesso** de ações e cores **semânticas** de feedback
  (info/sucesso/atenção/erro).
- **Implementação:** expor as cores do tenant como **CSS variables / tokens do
  Tailwind** (ex.: `--color-accent`, `--color-header`, `--color-bg`) injetadas por
  tenant; os componentes consomem o token, sem hardcode de cor de marca.

## Decisões em aberto

- [ ] Família tipográfica (ex.: Inter / system-ui) — e se é custom por tenant.
- [ ] Biblioteca de componentes sobre o Tailwind (ex.: Headless UI, shadcn,
      Flowbite) ou componentes próprios.
- [ ] Conjunto de ícones definitivo (Heroicons?).
- [ ] Mecanismo exato de injeção dos tokens de cor por tenant.

## Referências
- [Persona: Front-end & UX](./persona-frontend-ux.md)
- [Convenções](../ai-context/conventions.md)
- Tailwind CSS: https://tailwindcss.com/
