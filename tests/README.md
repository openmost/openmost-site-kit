# Tests

Development only, excluded from the release by `.distignore`.

```bash
cd tests
composer install
vendor/bin/phpunit                         # everything
vendor/bin/phpunit --testsuite unit        # no WordPress, no database, ~1 s
vendor/bin/phpunit --testsuite integration # real WordPress site, ~80 s
```

The integration suite builds a throwaway site in `tests/.tmp/site` on top of the
WordPress core set in `OMSK_TEST_WP_CORE` (`phpunit.xml`, defaults to the
WordPress install that holds this plugin), installs it in the
`omsk_site_kit_tests` database (dropped and recreated on every run, the name
must end with `_test` or `_tests`), serves it with the PHP built-in server and
records the tracking requests on a fake `matomo.php`. Override the `OMSK_TEST_DB_*`
variables in a local `phpunit.xml` copy if MySQL is not `root` without password.
