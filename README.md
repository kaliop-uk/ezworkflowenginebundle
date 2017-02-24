ezworkflowenginebundle
======================

A workflow engine for eZPublish5 / eZPlatform

Example workflow definition:

    -
        type: workflow
        slot: LocationService\HideLocationSignal
    
    -
        type: content
        mode: update
        new_remote_id: pippo
        match:
            content_id: reference:slot:content_id

*Stay tuned...*
