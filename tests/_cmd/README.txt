Installation
------------------

Require orkan/utils
Copy this dir [_cmd] to your project tests/[_cmd]

Set correct paths in:
_config.bat
phpunit.xml

Note:
The developement is done in the host project (outside of this package).
Use Composer "options": { "symlink": false } to correctly compute ABS paths in host project location.
Finally files are imported here as-is, thats why paths here are relative to external location.

Testing
------------------

[PHPUnit] *.bat
Double click to run all test suites.
To test separate files create shortcut in testing dir and drop *Test.php file on it.

After tests
------------------

composer dump --no-dev
or
composer update --no-dev
