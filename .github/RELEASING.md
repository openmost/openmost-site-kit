# Releasing Matomo Site Kit

Releases are published to WordPress.org by GitHub Actions. No local SVN checkout is needed.

## Steps

1. **Bump the version** (same number everywhere):
   - `openmost-site-kit.php`: `Version:` header and `OPENMOSTSITEKIT_VERSION`
   - `readme.txt`: `Stable tag:`
   - `package.json` and the two root entries of `package-lock.json`
2. **Update `readme.txt`**: add the `= X.Y.Z =` entry at the top of `== Changelog ==` (and of `== Upgrade Notice ==` when users should know something), update `Tested up to:` if needed.
3. **Rebuild** when `src/` changed: `npm run build`, commit `build/`.
4. **Regenerate the POT** when strings changed: `wp i18n make-pot . languages/openmost-site-kit.pot --slug=openmost-site-kit --domain=openmost-site-kit --exclude=tests,build,node_modules`.
5. **Run the tests**: `cd tests && composer install && vendor/bin/phpunit` (see `tests/README.md`).
6. **Commit and push** to `main`.
7. **Tag and push**: `git tag -a X.Y.Z -m "Matomo Site Kit X.Y.Z" && git push origin X.Y.Z`, or create a GitHub release with a new tag `X.Y.Z` (no `v` prefix).

The tag triggers `.github/workflows/deploy-wordpress-org.yml`, follow it in the Actions tab.

## What the workflow does

- Fails before touching SVN when the tag differs from the header `Version` or the readme `Stable tag`.
- Copies the repository to SVN `trunk/` (minus `.distignore`), then creates `tags/X.Y.Z`. An existing tag is never overwritten.
- Leaves SVN `assets/` (banners, icons, screenshots) untouched: they are managed directly in SVN, there is no `.wordpress-org/` folder here.
- Uses the repository secrets `SVN_USERNAME` (`openmost`) and `SVN_PASSWORD` (the SVN password generated on WordPress.org, Edit Profile, Account & Security, SVN credentials, not the login password).

## What ships

Everything tracked by git except the `.distignore` entries (`tests/`, `.github/`, `.claude/`, dot files). `src/` and `package*.json` ship on purpose, as the readable source of the minified `build/`.

## Readme-only changes

A change to `readme.txt` alone (for example `Tested up to`) only appears on WordPress.org with a new version, or by copying the file by hand into SVN `trunk/` and `tags/<stable>/`. The simplest path is a patch release (X.Y.Z+1).
