ezworkflowenginebundle
======================

A workflow engine for eZPublish5 / eZPlatform.


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
            content_id: workflow:signal:content_id


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

    Also, if you have enabled 'debug' level logging for Symfony, you will find that any time an eZPublish signal is
    emitted, the workflow engine add to the log 1 or 2 lines with information about its actions.

7. set up a cron job that will clean up the workflow table in the database by executing, fe. daily, the following:
 
        php ezpublish/console kaliop:workflows:cleanup


## Frequently asked questions

Q: how can I find out which content/location the current workflow is dealing with?

A: the workflow engine makes available as references the parameters found in the Signal which triggers the workflow.
    Ex: `workflow:signal:content_id`.
    Once you have a content or location id, you can use the steps 'content/load' and 'location/load' to get hold of the
    whole thing, and set new references to its other attributes, eg:
    
        - 
            type: content
            mode: load
            match:
                content_id: workflow:signal:content_id
            references:
                -
                    identifier: the_object_name
                    attribute: name

Q: how can I make a workflow act only on some specific content(s)?

A: use a workflow/cancel step with an 'unless' condition 

Q: are there steps which are specific to workflows, besides those found in the migrations bundle?

A: yes. Take a look at [the docs](Resources/config/doc/DSL/Workflow.yml)

Q: can I suspend a workflow and restart it later?

A: yes, just as you would with a migration (see the doc in the migrationbundle for details).
    NB: when a workflow is restarted, all the reference values that it had originally will be restored, however it might
    be that the contents that they refer to might have changed in the meantime.

Q: does the workflow engine emit Symfony Events when workflows are run?

A: yes. Currently the following events are emitted:
    * ez_workflow.before_execution
    * ez_workflow.step_executed
    * ez_workflow.migration_aborted
    * ez_workflow.migration_suspended
    Note: these events are not considered final and might change in the future

Q: can the workflow engine be used for scenarios where user interaction is required (eg. Content Approval ?)

A: not yet, and possibly never in a "good enough" way.
    The main reason for this is that all the eZPublish Signals are triggered 'after' an action takes place. As such, it
    is hard to keep a content in 'for approval' state after it has already been published.
    The 2nd reason is that at the moment there is no way to allow GUI interactions with existing workflows (but this could
    be easily worked around with dedicated steps)

Q: can I manage the workflows and triggers via a GUI, just like I used to do in eZPublish 4?

A: no, but you have command line tools to help with debugging and troubleshooting

Q: why are you storing the workflow definitions in configuration files instead of using the database?

A: this makes it much easier to have consistent workflows definitions across development/test/production environments,
    as well as Continuous Integration scenarios


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
