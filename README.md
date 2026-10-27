# Dowsing Machine

[![CI](https://github.com/jbourdin/dowsingMachine/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/jbourdin/dowsingMachine/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/jbourdin/dowsingMachine/graph/badge.svg)](https://codecov.io/gh/jbourdin/dowsingMachine)
[![PHP 8.5+](https://img.shields.io/badge/PHP-≥8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony 8.0](https://img.shields.io/badge/Symfony-8.0-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)

> **Audience:** Developer, Player · **Scope:** Overview

The application behind [dowsingmachine.com](https://dowsingmachine.com), a content site about the Pokemon TCG **Expanded format**: archetype pages and their variants, sample deck lists, the banned list and the format's staple cards, plus editorial pages. Content is written by a small team of editors and translated through a built-in review workflow.

Deck lists are imported by pasting the standard PTCG text format, then parsed and validated against **TCGdex**.

The project started as **Expanded Decks**, a tool to lend physical decks at events. That workflow was retired (see [#790](https://github.com/jbourdin/dowsingMachine/issues/790)), and its description now lives in [docs/legacy.md](docs/legacy.md). This is an independent community project, with no affiliation with The Pokemon Company.

## Stack

- **Backend:** PHP 8.5, Symfony 8.0, Doctrine ORM
- **Frontend:** React.js (via Symfony UX / Webpack Encore)
- **Database:** MySQL 8
- **Local development:** Docker, Docker Compose
- **External APIs:** TCGdex (card data, images)

## Features

| Domain | Summary |
|--------|---------|
| **Archetypes** | Archetype pages with variants, sample lists, mosaics and editorial content, managed by archetype editors. |
| **F2 — Deck lists** | Paste a list in PTCG text format, view it as a list or a card mosaic, export it (PTCGL text, Cardmarket wishlist). |
| **F6 — Card Data & Validation** | PTCG text parsing, validation via TCGdex, Expanded format rules, card images, banned list and staple cards pages, incremental TCGdex sync. |
| **F9 — Localization & Translation** | Translated interface, admin-managed channel locales, translation queue and review workflow for pages, archetypes and card lists. |
| **F11 / F17 — CMS** | Markdown content pages with a rich text editor, card and archetype reference tags, image upload, menu categories. |
| **F18 / F19 / F21 — SEO & Syndication** | Sitemap, canonical and hreflang tags, JSON-LD, Open Graph images, authorship, RSS feeds per category and for archetype variants. |
| **F14 — Deployment & Infrastructure** | Per-transport Messenger DSN, health check endpoints, Sentry error tracking. |

See the **[full feature list](docs/features.md)** for detailed descriptions and priorities. It still lists the retired lending features.

---

## Documentation

| Document                                      | Description                                |
|-----------------------------------------------|--------------------------------------------|
| [CLAUDE.md](CLAUDE.md)                        | AI context: coding standards & workflow    |
| [docs/docs.md](docs/docs.md)                  | Technical documentation entry point        |
| [docs/features.md](docs/features.md)          | Full feature list with priorities          |
| [docs/roadmap.md](docs/roadmap.md)            | Implementation roadmap                     |
| [docs/legacy.md](docs/legacy.md)              | The retired deck lending tool              |

## License

This project is licensed under the [Apache License 2.0](LICENSE).

## External References

- [TCGdex](https://tcgdex.dev/) — Multilingual Pokemon TCG card database
- [ptcgo-parser](https://github.com/Hamatti/ptcgo-parser) — PTCG text format parser
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Gitflow Workflow](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow)
