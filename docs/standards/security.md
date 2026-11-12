# Security Scanning

> **Audience:** Developer, AI Agent · **Scope:** Dependency vulnerability scanning, HTTP response security headers

← Back to [Standards](../docs.md#standards)

---

## Overview

The project uses automated dependency vulnerability scanning for both PHP and JavaScript ecosystems. Scans run locally via Make targets, in CI on every push/PR, and every week in the dependency report issue.

## Local Scanning

| Command          | Description                                  |
|------------------|----------------------------------------------|
| `make audit`     | Run both PHP and JS dependency audits        |
| `make audit.php` | Run PHP dependency audit only (`composer audit`) |
| `make audit.js`  | Run JS dependency audit only (`npm audit`)   |

These commands exit with a non-zero code when vulnerabilities are found, making them suitable for pre-commit checks.

## CI Integration

The **Security Audit** job in `.github/workflows/ci.yml` runs `composer audit` and `npm audit` on every push to `main`/`develop` and on every pull request. It runs in parallel with existing quality jobs and requires no database or build step.

If the job fails, it means one or more dependencies have known CVEs that must be addressed before merging.

## Weekly Dependency Report

Dependabot is not used: its weekly grouped PRs were replaced by the next week's before anyone could act on them. Instead, `.github/workflows/dependency-report.yml` runs every Monday (and on demand from the Actions tab) and rewrites a single pinned issue titled **Dependency report**, with three sections:

- **Security**: every installed package with a known advisory, direct or indirect (`composer audit --locked`, `npm audit`). The CI Security Audit job fails on these too.
- **Functional, patch and minor**: direct dependencies with a semver-compatible update (`composer outdated --locked --direct`, `npm outdated`).
- **Functional, major**: direct dependencies with a new major version.

The report is rendered by `.github/scripts/dependency_report.php`. Nothing is updated automatically: pick what to take, then update it by hand in a `chore/update-dependencies` branch and run `make audit` and `make test`.

## Composer Audit Blocking

The `composer.json` config includes `"block-insecure": true`, which causes `composer install` and `composer update` to fail if any installed package has a known vulnerability. This acts as a hard gate beyond just CI — developers cannot install dependencies locally without resolving advisories first.

## Held-Back Packages

Occasionally an upstream release is broken for us, or a major needs a coordinated migration, and must be held back:

1. For Composer, a `conflict` entry in `composer.json` is the hard gate, so a manual `composer update` cannot reintroduce it.
2. Every hold, Composer or npm, gets an entry in `.github/dependency-holds.json` (package name or glob → why, and the condition to lift it). The dependency report marks those packages with ⏸ and the reason.

| Package | Held at | Reason | Lift when |
|---------|---------|--------|-----------|
| `doctrine/orm` | `< 3.6.8` | 3.6.8 calls `Schema::edit()`, which only exists in `doctrine/dbal ^4.5`. DBAL 4.5 is unreleased (4.4.4 is latest stable), so every functional test dies in `SchemaTool` with a `BadMethodCallException`. | DBAL 4.5.0 ships — check `composer show doctrine/dbal --all` |
| Encore, Babel, webpack-cli, sass-loader, typescript, eslint majors | current majors | Coordinated build-toolchain migration, see #767 | #767 is done |

Removing a hold means dropping its entries and running the full suite.

## npm Overrides

When a transitive dependency has a vulnerability but its direct parent hasn't released a fix, use npm `overrides` in `package.json` to force a patched version:

```json
{
    "overrides": {
        "vulnerable-package": "^x.y.z"
    }
}
```

Verify compatibility by running `make assets` and `make test.front` after adding an override.

## Responding to Vulnerabilities

1. Run `make audit` to identify affected packages
2. For PHP: `symfony composer update <package>` to pull the patched version
3. For JS: `npm audit fix` for safe upgrades, or add an `overrides` entry for transitive deps
4. Verify with `make audit`, `make assets`, and `make test` / `make test.front`
5. Commit and push — the CI Security Audit job confirms the fix

## Response Security Headers

> **Feature:** F19.9 — Security/trust response headers (audit finding M6)

`App\EventListener\SecurityHeadersListener` adds a baseline set of trust/hardening
headers to every **main** response (sub-requests are skipped to avoid duplication).
It is a `kernel.response` listener, so it runs for all channels and routes without
per-controller wiring.

### Enforced headers (every response)

| Header | Value | Why |
|--------|-------|-----|
| `X-Content-Type-Options` | `nosniff` | Stops MIME-type sniffing (script-injection via mistyped responses). |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Sends only the origin cross-site; protects path/query leakage. |
| `X-Frame-Options` | `SAMEORIGIN` | Legacy clickjacking defence (paired with the CSP `frame-ancestors`). |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), interest-cohort=()` | Denies powerful features by default. Camera is denied too — there is no live camera scanner yet. When one ships, re-enable it as `camera=(self)` **on the app channel only** (the content channel never needs it). |
| `Strict-Transport-Security` | `max-age=86400; includeSubDomains` | HTTPS-only requests only (`$request->isSecure()`). Max-age is intentionally moderate (1 day) to start; ramp up and consider `preload` once every subdomain is confirmed HTTPS-only. |

### Content-Security-Policy (report-only, HTML responses)

CSP is shipped as **`Content-Security-Policy-Report-Only`** — it never blocks,
it only reports. The app still renders inline `<head>` scripts (theme colour
scheme, etc.), so a strict `script-src` would break them; report-only surfaces
exactly what needs a nonce before we can switch to enforcement. The enforcing
`Content-Security-Policy` header is deliberately **absent**.

The policy is applied only to HTML documents. A Twig-rendered `Response` has **no
`Content-Type` header yet at `kernel.response`** (the `text/html` default is set
later in `Response::prepare()`), so the listener treats an empty/absent
Content-Type as HTML. `JsonResponse`, the XML sitemap/feed, and image responses
set their Content-Type eagerly in their constructors and are therefore excluded.

Current report-only directives:

```
default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self';
form-action 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';
img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https:
```

`style-src` allows `'unsafe-inline'` (Bootstrap/Mantine inject inline styles —
lower risk than scripts). `img-src` is broad (`https:`) because card art (TCGdex),
sprites, editor uploads, and the CDN come from many hosts.

### CSP violation reporting (optional)

Set the `SECURITY_CSP_REPORT_URI` env var to a collector endpoint (e.g. a Sentry
security report URL) to append a `report-uri` directive. When unset, no
`report-uri` is emitted. This is the recommended next step before flipping CSP
from report-only to enforcing.
