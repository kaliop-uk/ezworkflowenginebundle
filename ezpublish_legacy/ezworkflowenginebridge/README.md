ezworkflowenginebridge
======================

This extension is designed to allow usage of the Workflow Engine from the Kaliop eZ Workflow Engine Bundle from an
eZPublish 4 context.

In other words: with it you can create eZPublish 4 workflows that will run any corresponding, predefined eZPublish 5 workflows.

It is important to note that eZPublish 5 workflows are triggered by the Signal-Slot mechanism, and that when content
is edited via the Legacy Administration interface, no Signal is emitted at all.
This means that, if you have defined eZPublish 5 workflows via the Kaliop eZ Workflow Engine Bundle:
- they will be triggered when editing content via the REST API or directly via custom PHP code which uses the Public API
- they will *not* be triggered when editing content via the Legacy Administration interface
When enabling and configuring this extension, the latter point becomes moot, and you can have workflow which are defined
once and run in all contexts.

For an in depth description of how the the Kaliop eZ Workflow Engine Bundle works, start with its README file. 

Setting it up
=============

1. Activate the extension in ActiveExtensions in site.ini.append.php 

2. log in to the Administration Interface using an administrator account and:

    - create a new Workflow, and add to it a single Workflow Event of type `Run WorkFlowEngineBundle workflows`
    
    - in the page listing all available Triggers, connect the workflow to the *AFTER* trigger for all the operations that
        you want to start available eZPublish 5 workflows

3. configure eZ5 workflows so that they are triggered by Signals corresponding to the triggers that you hooked up. Ex:

     ez4 trigger: content / hide / after => ez5 signal: LocationService\HideLocationSignal

4. test it: when enabling debug.log, you should see a line for every time the `Run WorkFlowEngineBundle workflows` event
     is executed as part of an ez4 workflow

FAQ
===

Q: can the `Run WorkFlowEngineBundle workflows` event be used for any type of content trigger?
A: yes. But you are probably better off if you hook it up to AFTER trigger.
    Actually, 2 triggers are currently not supported: content/delete and content/updateobjectstate. This is due to how
    those triggers work in eZ4 and is not easy to fix or work around.

Q: can the `Run WorkFlowEngineBundle workflows` event be used for custom triggers which come from legacy extensions?
A: yes. In this case the name of the Signal to use in your ez5 workflow definition will use a slightly different format,
     similar to `pre_$module_$action`, `post_$module_$action`. All the parameters defined in your operation will be made 
     available as references to the eZ5 workflow, converted from camel case to snake case.
