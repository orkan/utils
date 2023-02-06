INSTALLATION v3.3.0:
-------------
1. composer require orkan/utils
2. copy "vendor\orkan\utils\tests\_cmd" to your "vendor\[vendor_name]\[package_name]\tests\_cmd"
3. in case of different directory layout, correct the paths in:
	_config.bat
	phpunit.xml

USAGE:
------
Run tests directly from "vendor\[vendor_name]\[package_name]\tests" dir!

NOTES:
------
To correctly compute absolute paths, use Composer "options": { "symlink": false }
This package has been imported from [vendor] dir AS-IS, thats why all paths here are relative to external location.

TESTING YOUR PACKAGE:
---------------------
- double click any of "_cmd\[PHPUnit] *.bat" to run all test suites (in desired output format)
- to test separate files, create shortcut in testing subdir and drop *Test.php file on it.
