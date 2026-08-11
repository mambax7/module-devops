<?php

declare(strict_types=1);

/**
 * PHPStan bootstrap — XOOPS module DevOps baseline.
 *
 * Defines the legacy XOOPS constants/globals that static analysis needs to "see"
 * but that only exist at runtime inside a booted XOOPS instance.
 *
 * xoops-overlay:profile=core27
 */

// Common XOOPS path constants referenced by modules at analysis time.
if (! defined('XOOPS_ROOT_PATH')) {
    define('XOOPS_ROOT_PATH', __DIR__);
}
if (! defined('XOOPS_TRUST_PATH')) {
    define('XOOPS_TRUST_PATH', __DIR__);
}
if (! defined('XOOPS_URL')) {
    define('XOOPS_URL', 'https://localhost');
}
if (! defined('_CHARSET')) {
    define('_CHARSET', 'utf-8');
}

// The module's own language constants (_MI_*, _MA_*, _AM_*). The files are pure
// define() lists, so loading them lets analysis resolve every constant to a real
// string -- and catch a mistyped constant name -- instead of baselining each one
// as unknown. Explicit allowlist, never a glob: only named language-definition
// files are ever executed here, so a stray PHP file in language/english (or the
// anti-indexing index.php stub) cannot run during analysis. EDIT the list to
// match your module's language files (admin.php, main.php, modinfo.php,
// blocks.php, ...). The is_file() guard keeps the template copy-paste safe when
// an entry does not exist; a file you forget to LIST is loud, as PHPStan then
// reports every one of its constants as unknown.
foreach (['admin.php', 'main.php', 'modinfo.php'] as $xoopsLanguageFile) {
    $xoopsLanguagePath = __DIR__ . '/language/english/' . $xoopsLanguageFile;
    if (is_file($xoopsLanguagePath)) {
        require_once $xoopsLanguagePath;
    }
}
unset($xoopsLanguageFile, $xoopsLanguagePath);

// Profile target: XoopsCore27 / PHP 8.2+
