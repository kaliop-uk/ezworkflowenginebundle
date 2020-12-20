Version 1.1.3
=============

* No changes, mark as compatibile with eZMigrationBundle 5.x


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
