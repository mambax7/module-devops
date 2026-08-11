# Hosting a library that grabs global handlers

Some libraries take things that belong to the whole process: PHP's error and exception
handlers, shutdown functions, the session, the output buffer, `ini` settings, autoloaders,
headers. They do it because they were written for an application that owns the request.
Inside XOOPS your module does not own the request — it is one of many things loading during
a boot, and XoopsLogger, DebugBar and the error screen all have claims on the same globals.

So you will sometimes want a library's features **without** one of the side effects it
installs. This page is about how to do that without breaking the library.

## The rule

> **Refuse the side effect at the library's own injection seam. Do not let it happen and
> then undo it.**

Most libraries that grab a global offer a way to say no: a constructor argument, a config
flag, a driver or facade object, a factory you can subclass. Passing your own object through
that seam means the side effect never happens. Undoing it afterwards means it happened, and
you are now relying on the library not noticing.

## Why "undo it afterwards" fails

It usually works, right up until the library tidies up after itself.

Global hooks are almost always installed and removed as a **matched pair**: `register()` and
`unregister()`, `enable()` and `disable()`, and often a destructor that calls the second one
whether or not you asked. PHP's own handler functions make this concrete —
`set_error_handler()` **pushes** a frame and `restore_error_handler()` **pops** one; they are
not setter and getter.

So if the library pushes two frames and you quietly pop one of them back, the library's
teardown still pops two. One of those pops takes a frame that was never the library's, and
the request ends with the error handler one level below where it started — usually somewhere
that silently swallows notices. Nothing throws. Nothing logs. You find it months later.

The worked example this rule came from: a Whoops provider needed the exception and shutdown
handlers but not the error handler. `restore_error_handler()` immediately after
`$whoops->register()` looked like the obvious fix, passed its tests, and was wrong —
`Run::unregister()`, called by `Run::__destruct()`, restores **both** handlers. The fix that
holds is to pass `Run::__construct()` a `Whoops\Util\SystemFacade` subclass whose
`setErrorHandler()` and `restoreErrorHandler()` do nothing, so Whoops is never given the
error handler and its own pairing stays balanced by construction.

## Does it apply here?

| Use the seam when | Do something else when |
|---|---|
| The library exposes a facade, driver, factory or constructor argument for its global hooks | It offers only a global `enable()` with no injection point |
| Its lifecycle is paired — register/unregister, enable/disable, or a destructor that cleans up | Nothing is ever torn down, so there is no pairing to break |
| You want a documented **subset** of what it installs by default | You actually want everything it installs |
| The subset is part of your module's contract with the site | Refusing would break the feature you are shipping |

### When there is genuinely no seam

Undo-after is not the fallback. It is the thing this page tells you not to do, and having no
alternative does not make it safe — it makes the integration **unsupported until you have
done the work to know it is safe**. Concretely, before you ship a `restore_*`:

- read the library's setup path, its explicit teardown, **and** its destructor, and know
  which of them touch the global you are taking back;
- know whether the library's teardown assumes it still owns what you took — that is the
  failure that motivated this page, and it is invisible until something destructs;
- **write a test that asserts the process-wide state is what it was**, exercised across the
  library's full lifetime including teardown. Not a comment. A comment records your belief;
  a test notices when the belief stops being true, which is usually at the next upgrade.

If you cannot do those three, do not host the library that way. Ask upstream for a seam —
that is how `SystemFacade` came to exist in Whoops — or keep the side effect and design
around it.

## Before you ship one

1. **Prefer an official option.** A documented config flag beats a subclass.
2. **Use a public seam.** Never reflection, never a vendor edit, never `@` on the symptom.
3. **Copy the parent signature verbatim.** Adding a type to an inherited untyped parameter
   is a fatal at class-declaration time, on every request. Refactoring and static-analysis
   tools propose exactly this — write a comment saying no, because you will be asked again.
   (Adding a *return* type where the parent declares none is fine and covariant-safe.)
4. **Check the library does not use what you refused.** If it stores the return value of the
   method you neutered, returning `null` may not be harmless.
5. **Test the teardown, not just the setup.** The bug above is invisible if you only assert
   state after `register()`. Assert it after `unregister()` too — and after the object goes
   out of scope, if it has a destructor.
6. **Re-check on upgrade.** You are depending on a shape the library did not promise to
   keep. Pin the dependency or re-run the teardown test when you bump it.

## Libraries this comes up with

`SystemFacade` is Whoops' door and nothing else's. Tracy, Monolog, Symfony's `ErrorHandler`
and Sentry each install global hooks through a different one, and some of them through
several. The reflex to carry is not an API name:

> **Look for the injection point before reaching for `restore_*`.**

---

*Origin: XOOPS 2.7.3's error-screen provider seam, where the first attempt at this was
wrong in the way described above. The seam-specific decision — why xWhoops refuses the error
handler in particular — lives in the error-screen provider ADR that ships with that feature.
The rule is general and lives here.*
