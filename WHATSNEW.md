Version 2.1
===========

* New: command `kaliop:workflows:workflow` learned action `--fail`

* New: command `kaliop:workflows:debug` learned option `--show-path`

* BC change (for developers extending the bundle): `WorflowServiceInner` methods `executeMigration` and `executeMigrationInner`
  should now be called using a different signature. They do still work with the previous signature, but that usage is
  considered deprecated


Version 2.0
===========

* Make compatible with eZMigrationBundle 5.13 and later (this means we have to drop compatibility with earlier versions)

* Fix command `kaliop:workflows:workflow --info` to be compatible with php 7.4

* Move from `master` to `main` branch

* Move from Travis to GitHub Actions for CI testing. Add some proper tests!

* Bump the version of phpunit used to run the tests from 4.x/5.x to 5.x/8.x


Version 1.1.3
=============

* No changes, mark as compatible with eZMigrationBundle 5.x


Version 1.1.2
=============

* Fixed errors with recent versions of Symfony (3.0 and up)


Version 1.1.1
=============

* Fixed fatal error introduced in 1.1


Version 1.1
===========

* New: it is now possible to define the following parameters using siteaccess-aware configuration:

    `ez_workflowengine_bundle.table_name`, `ez_workflowengine_bundle.workflow_directory`

    This is useful when you need to execute different sets of workflows for each site in a multi-site installation.

* Changed: requires Kaliop eZ-Migration Bundle version 4.7 or later.

    NB: recent versions of the eZ-Migration Bundle have introduced support for multiple features useful for
    workflows. Be sure to check out its WHATSNEW file.


Version 1.0
===========

Initial release
