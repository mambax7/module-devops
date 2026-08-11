# Changelog

All notable changes to this module are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this module adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

<!-- Replace the placeholders below with your module's real name, repo URL, and
     release history. Add a new dated section under each tagged release;
     keep day-to-day changes under [Unreleased] until you cut the next tag. -->

## [Unreleased]

### Added

- `stubs/xoops.stub`: the XOOPS 2.7.3 error-screen ownership API —
  `xoops_recordErrorScreenOwner()`, `xoops_getRecordedErrorScreenOwner()` and
  `xoops_releaseErrorScreenOwner()`. Every error-screen provider calls these from its
  install/update/uninstall hooks, and they were being hand-copied into each module's local
  stub. Declared with the caveat attached: a `core27` profile describes the newest 2.7 API,
  so a module whose `min_xoops` is below 2.7.3 must keep its `function_exists()` guards.
- `docs/hosting-a-library-that-grabs-global-handlers.md`: how to stop a Composer package
  taking PHP's error handler, the session or the output buffer out from under XOOPS —
  refuse the side effect at the library's injection seam rather than undoing it afterwards,
  which breaks the library's own register/unregister pairing.
- README: an *Extending the stub* section, because declaring a class in the shared stub
  converts one `class.notFound` into a `method.notFound` per undeclared method and quietly
  invalidates matching baseline entries in every consuming module.

### Changed

### Fixed

## [1.0.0] - YYYY-MM-DD

### Added

- Initial release.

[Unreleased]: https://github.com/XoopsModules27x/module-devops/commits/master
[1.0.0]: https://github.com/XoopsModules27x/module-devops/releases/tag/1.0.0
