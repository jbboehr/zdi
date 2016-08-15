# Changelog

All Notable changes to `zdi` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased]

### Added
- `addNamespace` and `addNamespaces` accept a callable which is passed the DefinitionBuilder for each matched class

### Fixed
- `NodeVisitor::beforeTraverse` does not recurse, causing interface injection to be ignored and factory value to not be
assigned in closures with `return`s not at the top level.

## [0.1.4] - 2016-08-01

### Added
- Interface injection support
- Injection point support

## [0.1.3] - 2016-07-21

### Added
- Use [container-interop](https://github.com/container-interop/container-interop) interfaces.

## [0.1.2] - 2016-07-14

### Added
- Ability to declare an identifier as a global parameter, to be used for unresolved parameters of the same name

## 0.1.1 - 2016-07-12
- Initial release

[Unreleased]: https://github.com/jbboehr/zdi/compare/v0.1.4...HEAD
[0.1.4]: https://github.com/jbboehr/zdi/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/jbboehr/zdi/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/jbboehr/zdi/compare/v0.1.1...v0.1.2
