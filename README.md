Kaliop eZWorkflowEngine Bundle
==============================

A workflow engine for eZPublish5 / eZPlatform 1 and 2.


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

In plain words, this means: when any content is hidden, set its 'remote_id' value to 'pippo'.

More examples of how "real-life" workflows do look like:

* [Add a screenshot automatically when a video file is uploaded](Resources/doc/Examples/AddScreenshot.yml)
* [Fill contents with 'lorem ipsum' text using an online service](Resources/doc/Examples/FillComments.yml)
* [Fire a mail alert if an editor moves content that he's not supposed to alter](Resources/doc/Examples/MailOnMove.yml)


## Getting started

1. Install the bundle using Composer, enable `EzWorkflowEngineBundle` in your application Kernel (if you had not previously
    installed the eZMigrationBundle, you should enable as well `EzMigrationBundle` - Composer will have downloaded it
    automatically for you, but not enabled it)

2. Create a workflow definition in any bundle you want by using the dedicated command

        php bin/console kaliop:workflows:generate myBundle

3. Edit the workflow definition

    - change the value for the 'signal' element to the name of an existing eZPublish Signal (see https://doc.ez.no/display/EZP/Signals+reference)
    - add the required steps to your workflow (see the docs from the kaliop migration bundle for possible steps definitions)

4. test that the workflow is listed as available

        php bin/console kaliop:workflows:debug

5. connect to the Administration Interface, execute the desired content modification action.
    (Note: if you are using the Legacy Admin interface, you will need to execute extra work. Read the dedicated chapter below)

6. check that the workflow you defined did execute

        php bin/console kaliop:workflows:status

    Also, if you have enabled 'debug' level logging for Symfony, you will find that any time an eZPublish signal is
    emitted, the workflow engine add to the log 1 or 2 lines with information about its actions.

7. set up a cron job that will pick up and restart any suspended workflow by executing, fe. every 5 minutes, the following:

        php bin/console kaliop:workflows:resume -n

8. set up a cron job that will clean up the workflow table in the database by executing, fe. daily, the following:

        php bin/console kaliop:workflows:cleanup

9. once your workflow is debugged and tested, don't forget to commit the workflow definition file as part of your source code ;-)


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

A: yes. Take a look at [the docs](Resources/doc/DSL/Workflow.yml)

Q: how can I troubleshoot workflows?

A: by default workflow execution steps and triggers are logged in detail as part of the Symfony log, at DEBUG level.
     If you want to have a separate log file dedicated to troubleshoot only workflow events, you can add to your config.yml
     something similar to fe.

        monolog:
            handlers:
                workflow_log:
                    type: stream
                    path: "%kernel.logs_dir%/%kernel.environment%.workflow.log"
                    level: debug
                    channels: [ ez_workflow ]

Q: can I suspend a workflow and restart it later?

A: yes, just as you would with a migration (see the doc in the migrationbundle for details).
    NB: when a workflow is restarted, all the reference values that it had originally will be restored, however it might
    be that the contents that they refer to have changed in the meantime.

Q: does the workflow engine emit Symfony Events when workflows are run?

A: yes. Currently, the following events are emitted:

  * ez_workflow.before_execution
  * ez_workflow.step_executed
  * ez_workflow.workflow_aborted
  * ez_workflow.workflow_suspended

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

Q: are workflow definitions cached ?

A: yes. For speed of execution, the bundle caches the parsed workflow definitions attached to each signal.
    This means that, if you are running Symfony in non-dev mode, you will have to clear the Symfony cache each time that
    you modify the definition of an existing workflow or add/remove workflows (using f.e. the `cache:clear` command)


## Integration with the Legacy Administration Interface

By default, editing actions carried out in the Legacy Administration Interface do not trigger execution of the workflows
defined in your yml files.
It is however possible to do so, with just a little bit of configuration work:
- enable the extension `ezworkflowenginebridge` in settings.ini.override.php
- clear caches
- in the Administration Interface, create custom Legacy Workflows and hook them up to Triggers as per the documentation
     that you will find in the [readme](ezpublish_legacy/ezworkflowenginebridge/README.md)

## Running tests

The bundle uses PHPUnit to run functional tests.

#### Running tests in a working eZPublish / eZPlatform installation

To run the tests:

    export KERNEL_DIR=app (or 'ezpublish' for ezpublish 5.4/cp setups)
    export SYMFONY_ENV=behat (or whatever your environment is)

    bin/phpunit --stderr -c vendor/kaliop/ezworkflowenginebundle/phpunit.xml.dist

*NB* the tests do *not* mock interaction with the database, but create/modify/delete many types of data in it.
As such, there are good chances that running tests will leave stale/broken data.
It is recommended to run the tests suite using a dedicated eZPublish installation or at least a dedicated database.

#### Setting up a dedicated test environment for the bundle

A safer choice to run the tests of the bundle is to set up a dedicated environment, similar to the one used when the test
suite is run on GitHub Actions.
The advantages are multiple: on one hand you can start with any version of eZPublish you want; on the other you will
be more confident that any tests you add or modify will also pass on GitHub.
The disadvantages are that you will need Docker and Docker-compose, and that the environment you will use will look
quite unlike a standard eZPublish setup! Also, it will take a considerable amount of disk space and time to build.

Steps to set up a dedicated test environment and run the tests in it:

    git clone --depth 1 https://github.com/tanoconsulting/euts.git teststack
    # if you have a github auth token, it is a good idea to copy it now to teststack/docker/data/.composer/auth.json

    # this config sets up a test environment with eZPlatform 2.5 running on php 7.4 / ubuntu jammy
    export TESTSTACK_CONFIG_FILE=Tests/environment/.euts.2.5.env

    ./teststack/teststack build
    ./teststack/teststack runtests
    ./teststack/teststack stop

You can also run a single test case:

    ./teststack/teststack runtests ./Tests/phpunit/01_CommandsTest.php

Note: this will take some time the 1st time your run it, but it will be quicker on subsequent runs.
Note: make sure to have enough disk space available.

In case you want to run manually commands, such as the symfony console:

    ./teststack/teststack console cache:clear

Or easily get to a database shell prompt:

    ./teststack/teststack dbconsole

Or command-line shell prompt to the Docker container where tests are run:

    ./teststack/teststack shell

The tests in the Docker container run using the version of debian/php/mysql/eZPlatform kernel specified in the file
`Tests/environment/.euts.2.5.env`, as specified in env var `TESTSTACK_CONFIG_FILE`.
If no value is set for that environment variable, a file named `.euts.env` is looked for.
If no such file is present, some defaults are used, you can check the documentation in ./teststack/README.md to find out
what they are.
If you want to test against a different version of eZ/php/debian, feel free to:
- create the `.euts.env` file, if it does not exist
- add to it any required var (see file `teststack/.euts.env.example` as guidance)
- rebuild the test stack
- run tests the usual way

You can even keep multiple test stacks available in parallel, by using different env files, eg:
- create a file `.euts.env.local` and add to it any required env var, starting with a unique `COMPOSE_PROJECT_NAME`
- build the new test stack via `./teststack/teststack. -e .euts.env.local build`
- run the tests via: `./teststack/teststack -e .euts.env.local runtests`


[![License](https://poser.pugx.org/kaliop/ezworkflowenginebundle/license)](https://packagist.org/packages/kaliop/ezworkflowenginebundle)
[![Latest Stable Version](https://poser.pugx.org/kaliop/ezworkflowenginebundle/v/stable)](https://packagist.org/packages/kaliop/ezworkflowenginebundle)
[![Total Downloads](https://poser.pugx.org/kaliop/ezworkflowenginebundle/downloads)](https://packagist.org/packages/kaliop/ezworkflowenginebundle)

[![Build Status](https://github.com/kaliop-uk/ezworkflowenginebundle/actions/workflows/ci.yml/badge.svg)](https://github.com/kaliop-uk/ezmigrationbundle/actions/workflows/ci.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kaliop-uk/ezworkflowenginebundle/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/kaliop-uk/ezworkflowenginebundle/?branch=main)
[![Code Coverage](https://codecov.io/gh/kaliop-uk/ezworkflowenginebundle/branch/main/graph/badge.svg)](https://codecov.io/gh/kaliop-uk/ezworkflowenginebundle/tree/main)
