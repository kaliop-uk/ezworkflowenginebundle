ezworkflowenginebundle
======================

A workflow engine for eZPublish5 / eZPlatform

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

*Stay tuned...*
