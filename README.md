kw_autoload
================

Personal autoloader for KWCMS. Far simplier than Composer. But with tests. No need
for remote CLI. Basic variant contains no cache. But you can use supplied cache or
write your own.

Only thing this cannot use is your own functions directly in files - everything must
be packed in classes. You just did not find them.

Installation
------------

Copy whole project into your vendor dir and copy things from ```example/_bootstrap.php```
to your bootstrap. Beware, there are two version of configuration. You must select what
exactly you want!

Setting
-------

### Paths

Paths are set from external source - not inside as you're probably used to know. So
you must define your file structure and say that to autoloader in form of masks with
defined properties. He doesn't want to make full lookup through hiearchy each time
the file is need. That consume too much resources.

The mask is formatted in _sprintf()_ format and has following params:
 * %1$s - directory separator by your OS
 * %2$s - path to project, set by _Autoload::setBasePath()_, usually __ DIR __ in root
 * %3$s - submodule vendor
 * %4$s - module name

The rest of class namespace/path is appended and got php extension.

### Add/Change paths on-the-fly

The setting path by mask allows to add another paths on-the-fly. So if there is
something unusual in path creation, it's possible to add that after bootstrap time.
Main example is where the files are in ```_vendor/_src/path``` (with underscore), but
autoloader is set to ```vendor/src/path``` (without underscore).

Tests
-----

It contains tests in directory ```/tests``` - just make ```/tests/Testing.php```
executable and run them. No PHPUnit, just php interpreter - again this is too low level
for something like full unit testing.
