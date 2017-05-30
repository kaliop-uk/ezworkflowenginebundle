ezworkflowenginebundle
======================

A workflow engine for eZPublish5 / eZPlatform


## How it works, in 10 lines of configuration 

Example workflow definition:

    -
        type: workflow
        signal: LocationService\HideLocationSignal
        run_as: admin

    -
        type: content
        mode: update
        new_remote_id: pippo
        match:
            content_id: reference:signal:content_id


## Getting started

1. Install the bundle using composer, enable `EzWorkflowEngineBundle` in your application Kernel

2. Create a workflow definition in any bundle you want by using the dedicated command

        php ezpublish/console kaliop:workflows:generate myBundle

3. Edit the workflow definition

    - change the value for the 'signal' element to the name of an existing eZPublish Signal (see https://doc.ez.no/display/EZP/Signals+reference)
    - add the required steps to your workflow (see the docs from the kaliop migration bundle for possible steps definitions)

4. test that the workflow is listed as available

        php ezpublish/console kaliop:workflows:debug

5. connect to the Administration Interface, execute the desired content modification action.
    (Note: if you are using the Legacy Admin interface, you will need to execute extra work. Read the dedicated chapter below)

6. check that the workflow you defined did execute

        php ezpublish/console kaliop:workflows:status

7. set up a cron job that will clean up the workflow table in the database by executing, fe. daily, the following:
 
        php ezpublish/console kaliop:workflows:cleanup


## Frequently asked questions

Q: are there steps which are specific to workflows, besides those found in the migrations bundle?
A: yes. Go look in [the docs](Resources/config/doc/DSL/Workflow.yml)

Q: can I suspend a workflow and restart it later?
A: yes, just as you would with a migration (see the doc in the migrationbundle for details).
    NB: when a workflow is restarted, all the reference values that it had originally will be restored, however it might
    be that the contents that they refer to might have changed in the meantime.


## Integration with the Legacy Administration Interface

By default, editing actions carried out in the Legacy Administration Interface do not trigger execution of the workflows
defined in your yml files.
It is however possible to do so, with just a little bit of configuration work:
- enable the extension `zworkflowenginebridge` in settings.ini.override.php
- clear caches
- in the Administration Interface, create custom Legacy Workflows and hook them up to Triggers as per the documentation
     that you will find in the [readme](ezpublish_legacy/ezworkflowenginebridge/README.md)


[![License](https://poser.pugx.org/kaliop/ezworkflowenginebundle/license)](https://packagist.org/packages/kaliop/ezworkflowenginebundle)
[![Latest Stable Version](https://poser.pugx.org/kaliop/ezworkflowenginebundle/v/stable)](https://packagist.org/packages/kaliop/ezworkflowenginebundle)
[![Total Downloads](https://poser.pugx.org/kaliop/ezworkflowenginebundle/downloads)](https://packagist.org/packages/kaliop/ezworkflowenginebundle)
