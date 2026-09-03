# Release process

Nexus releases are cut from `main` only after the exact release commit has passed all required CI gates.

## Required gates

Before creating a release tag:

1. `composer validate --strict`
2. PHPUnit on PHP 8.4 and PHP 8.5
3. PHPStan at level max
4. PER-CS coding-standard check
5. `composer audit --no-interaction`
6. benchmark smoke test
7. clean package installation smoke test
8. PostgreSQL live integration
9. MySQL live integration
10. SQL Server live integration
11. Oracle live integration
12. Docker Compose validation and build smoke for FrankenPHP, PHP-FPM + Nginx, RoadRunner and OpenSwoole

A release must not be tagged from a commit different from the commit that passed the gates.

## Versioning

Nexus follows Semantic Versioning.

- Release candidate tags use `vMAJOR.MINOR.PATCH-rc.N`.
- Stable releases use `vMAJOR.MINOR.PATCH`.
- The Composer package does not hard-code a `version` field; Composer derives the package version from Git tags.
- Before 1.0, backwards-incompatible public API changes may occur between minor versions and must be documented in the changelog.

## Release files

Every release must update:

- `CHANGELOG.md`
- `docs/releases/<tag-without-v>.md`
- `docs/SUPPORT_MATRIX.md` when support boundaries changed
- `docs/ROADMAP.md` when a release milestone changes state

## Package validation

The package-smoke workflow verifies two forms of consumption:

- the repository package can be installed into a clean Composer consumer without root `require-dev` dependencies;
- the generated Composer archive can install its production dependencies and execute `bin/nexus`.

This is separate from Packagist indexing. Packagist availability must be verified independently after the first published tag.

## Publishing

The release workflow is intentionally explicit and manual. It receives a version, verifies that the requested tag does not already exist, runs package validation from `main`, creates an annotated release tag and publishes a GitHub prerelease or stable release using the matching notes file.

For `v0.1.0-rc.1`, use version `v0.1.0-rc.1` and mark it as a prerelease.

## Post-release verification

After publication:

- verify the GitHub tag points to the intended release commit;
- verify the GitHub Release is marked prerelease/stable correctly;
- verify Packagist sees the tag if the package is registered there;
- install the published version in a clean external Composer project;
- run `vendor/bin/nexus about`;
- record any release-specific issue before moving to the next RC or stable release.
