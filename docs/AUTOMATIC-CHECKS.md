# Automatic checks while GitHub Actions is blocked

## Activate once per clone

```sh
composer install
npm ci
php tools/install-hooks.php
```

After this, a normal `git push` runs the PHP tests, public JavaScript syntax checks,
and the production asset build before contacting the remote with an update. Failure
returns a nonzero hook status and prevents that push. Dependencies must already be
installed; the hook does not install or update packages. PHP and Node must be on PATH.

The hook requires a clean checkout and validates the commit being pushed against
the current HEAD. Commit or stash pending changes first. Push other branches from
their own checkouts. Branch deletion requires no application tests.

## Run manually

```sh
php tools/check-release.php
php tools/check-release.php --mysql
```

Default PHP tests are explicitly isolated in in-memory SQLite, fake mail and array
cache. The optional MySQL suite uses the existing isolated-database helper; it
creates and removes only a newly generated test DB. Cached Laravel config causes
the runner to stop rather than risk testing against application settings.

Latest result (commit, UTC timestamp, step exit codes) is in
`storage/logs/local-checks.log`, ignored by Git. Test output appears in the pushing
terminal. Build output is regenerated under ignored public/build.

## Scope

This is a local pre-push check, not a hosted GitHub status. It does not clear the
GitHub account billing lock or make Actions green. Hooks are not installed by clone
automatically; every contributor must opt in using the installer. Browser-based
GitHub edits and pushes from other clones do not use this hook. Git can bypass local
hooks, so this is a developer guard, not remotely enforced branch protection.
Dependency audits and the isolated MySQL suite remain additional release checks;
the regular pre-push check does not claim to perform them.

Keep `.github/workflows/ci.yml`: hosted checks can resume once GitHub unlocks the
account. Do not create more empty commits to repeatedly retry a confirmed lock.
