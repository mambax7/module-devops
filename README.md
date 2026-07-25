# XOOPS Module DevOps Baseline (`module-devops`)

The **standard DevOps baseline** for XOOPS modules — drop it into any module (new
or existing): consistent GitHub templates, CI, Dependabot, and local quality tooling
(PHPStan, PHP-CS-Fixer, Rector, PHPUnit) — so every module behaves the same way on
GitHub and ships clean release archives.

> This repo is **config + tooling only** — it has no module code. The full module
> *starter* (with `admin/`, `preloads/`, `templates/`, `xoops_version.php`, …, built
> on top of this baseline) is a separate repo, **`module-skeleton`**.

**Target:** XoopsCore27 / **PHP 8.2+**. (For XoopsCore25/PHP 7.4 or a future
XoopsCore40 baseline, the dependency versions and CI matrix differ — ask the
maintainers for the matching variant.)

---

## What's in here

| File / dir | Purpose |
|---|---|
| `.github/workflows/ci.yml` | **Self-contained** quality gate — runs `composer qa` (CS-Fixer, PHPStan, Rector, PHPUnit) on PHP 8.2/8.3/8.4 on every push/PR (see [CI](#continuous-integration)). Swappable for a shared org-wide reusable workflow. |
| `.github/workflows/dependabot-auto-merge.yml` | Auto-merges Dependabot patch/minor PRs once CI is green. |
| `.github/workflows/release.yml` | On a version tag, builds a **clean** ZIP (via `git archive`, honoring `export-ignore`) and attaches it to the GitHub Release. |
| `.github/workflows/sonar.yml`, `sonar-project.properties` *(optional)* | **Opt-in** SonarCloud security/SAST + quality dashboard — inert until a `SONAR_TOKEN` secret is set (see [Security analysis](#security-analysis-optional--sonarcloud)). |
| `.github/dependabot.yml` | Weekly Composer + GitHub-Actions updates, grouped to cut noise. |
| `.github/ISSUE_TEMPLATE/`, `PULL_REQUEST_TEMPLATE.md`, `CODE_OF_CONDUCT.md`, `CONTRIBUTING.md`, `SECURITY.md`, `FUNDING.yml` | Community-health files. If your repo lives in an org that provides these centrally, you may delete the local copies to inherit the org-wide versions. |
| `composer.json` | Module identity + the standard `require-dev` and `scripts`. **Rename** `name`/namespace for your module. |
| `.php-cs-fixer.dist.php` | Coding-style rules (PSR-12 + XOOPS adjustments). The **only** style engine used. |
| `phpstan.neon.dist`, `phpstan-baseline.neon`, `phpstan-bootstrap.php`, `stubs/xoops.stub` | Static analysis config. The stub teaches PHPStan the core XOOPS classes (XoopsObject, handlers, Criteria, `Xmf\Module\Helper`) so analysis is meaningful without a full XOOPS runtime. |
| `rector.php` | Automated refactoring, pinned to the target PHP level (only scans directories that exist). Ships the **`xoops/rector-xoops`** rule set (`XoopsSetList::XOOPS`) so XOOPS-specific modernisation (DB `query`/`exec` split, removed PHP funcs, MyTextSanitizer + Smarty API renames) runs alongside the generic PHP sets. |
| `phpunit.xml.dist`, `tests/` | Test config + a guarded bootstrap. Put unit tests in `tests/Unit/`, integration tests in `tests/Integration/`. |
| `.gitattributes` | `export-ignore` rules — keeps dev/CI files **out** of the release ZIP. |
| `.editorconfig`, `.gitignore`, `.git-blame-ignore-revs` | Editor consistency, ignores, and a clean `git blame` after the first auto-format. |
| `CHANGELOG.md` | Keep-a-Changelog template — fill in your module's release history. (`export-ignore`d, so it stays out of the release ZIP.) |

---

## Apply it to a module

1. Copy these files into your module repository — a **new** module, or an **existing**
   one you're standardising (or use this repo as a GitHub template).
2. In `composer.json`, set your real `name`, `description`, `support` URLs, and the
   PSR-4 namespace (replace the placeholder `XoopsModules\Yourmodule\`). Point the
   autoload paths at your code (`class/` and/or `src/`).
3. Add your module code as usual (`xoops_version.php`, `admin/`, `class/`, `blocks/`,
   `language/`, `templates/`, SQL, …). The tooling here never touches business code.
4. Point the community-health files at your repo: edit the URLs in
   `.github/ISSUE_TEMPLATE/config.yml` (Discussions / Security) to your module's GitHub
   repo — **or** delete the local `.github/` community files to inherit your org's defaults.
5. Install dev tooling and run the quality gate:

   ```bash
   composer install
   composer qa
   ```

## Quality commands

| Command | What it does |
|---|---|
| `composer qa` | Full gate: style check → PHPStan → Rector (dry-run) → PHPUnit. |
| `composer cs:check` / `composer cs:fix` | Check / auto-fix coding style (PHP-CS-Fixer). |
| `composer analyse` | Run PHPStan. `composer analyse:baseline` regenerates the baseline. |
| `composer rector` / `composer rector:fix` | Preview / apply Rector refactorings. |
| `composer test` | Run the PHPUnit suite. |

CI runs the **check** variants and fails on any diff — fixes are applied locally
with `cs:fix` / `rector:fix`, never auto-committed to your branch by CI.

## Writing tests

`tests/bootstrap.php` runs in two modes automatically:

- **Unit-only** (default locally and in plain CI): no XOOPS runtime needed; just
  Composer autoload. Put pure tests in `tests/Unit/`.
- **Integration**: when a configured XOOPS is reachable, the bootstrap boots it.
  Integration tests should `use \RequiresXoops;` (the trait is in the global namespace, so
  the leading backslash matters in a namespaced test class) and call `$this->requiresXoops()`
  so they self-skip when no XOOPS runtime is present (instead of failing):

  ```php
  final class HandlerTest extends \PHPUnit\Framework\TestCase
  {
      use \RequiresXoops;

      protected function setUp(): void
      {
          $this->requiresXoops();
      }
  }
  ```

## Continuous integration

`ci.yml` is a **self-contained** quality gate: it runs `composer qa` (CS-Fixer
check → PHPStan → Rector dry-run → PHPUnit) across the supported PHP matrix on
every push and pull request — so the baseline enforces its own standard out of the box.

```yaml
jobs:
  qa:
    strategy:
      matrix:
        php: ['8.2', '8.3', '8.4', '8.5']
    steps:
      - uses: actions/checkout@34e114876b0b11c390a56381ad16ebd13914f8d5 # v4
      - uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240 # v2
        with: { php-version: '${{ matrix.php }}' }
      - run: composer install --no-interaction --no-progress
      - run: composer qa
```

> **Want one shared workflow for the whole org instead?** Publish a reusable
> `module-ci.yml` (tagged `v1`) in your org's `.github` repository, then replace the
> job above with a one-line caller — improve CI once, for every module at once:
>
> ```yaml
> jobs:
>   ci:
>     uses: XoopsModules27x/.github/.github/workflows/module-ci.yml@v1
>     with: { profile: core27, php_matrix: '["8.2","8.3","8.4"]' }
>     secrets: inherit
> ```

## Security analysis (optional — SonarCloud)

`ci.yml` already runs deep static analysis (PHPStan level 8 + strict rules), but it doesn't do
**security-focused SAST** — taint tracking, injection/XSS, security hotspots. The baseline ships an
**optional** SonarCloud scan for that, which is worth it because GitHub's free CodeQL doesn't support PHP.

It's **opt-in and inert by default**: `.github/workflows/sonar.yml` skips itself (with a notice) until a
`SONAR_TOKEN` secret exists — so it's a no-op for modules that don't use it and for fork PRs. It runs
*alongside*, not instead of, `composer qa`.

To enable it for a module:

1. Import the repo into [SonarCloud](https://sonarcloud.io) (free for public projects).
2. In `sonar-project.properties`, set `sonar.organization` and `sonar.projectKey` to match your SonarCloud project.
3. Add a **`SONAR_TOKEN`** secret under *Settings → Secrets and variables → Actions*.
4. *(Optional)* install the SonarCloud GitHub App for pull-request decoration.

## Release archives

Tag a release (e.g. `git tag 1.0.0 && git push --tags`). `release.yml` runs
`git archive`, which respects the `export-ignore` entries in `.gitattributes`, so
the published ZIP contains only what a site administrator should install — no
`.github/`, `tests/`, `phpstan*`, `rector.php`, etc.

## Requirements

- PHP 8.2+ and Composer 2 (for the dev tooling; the module itself runs under XOOPS).
- XoopsCore27 for integration tests.

## License

GNU GPL 2.0 or later. See the XOOPS project for details.
