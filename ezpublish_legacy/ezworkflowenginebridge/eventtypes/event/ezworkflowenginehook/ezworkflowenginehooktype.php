<?php

class eZWorkflowEngineHookType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezworkflowenginehook';

    static $signalMapping = array(
        'content_addlocation' => array( 'pre' => false, 'post' => 'LocationService\CreateLocationSignal' ),
        // this legacy operation does not allow us to retrieve in all scenarios the object_id, needed for the corresponding signal
        //'content_delete' =>  array( 'pre' => false, 'post' => 'ContentService\DeleteContentSignal' ),
        'content_hide' => array( 'pre' => false, 'post' => 'LocationService\HideLocationSignal' ),
        'content_move' => array( 'pre' => false, 'post' => 'LocationService\MoveSubtreeSignal' ),
        'content_publish' => array( 'pre' => false, 'post' => 'ContentService\PublishVersionSignal' ),
        // this legacy operation does not allow us to retrieve in all scenarios the object_id, needed for the corresponding signal
        //'content_removelocation' => array( 'pre' => false, 'post' => 'LocationService\DeleteLocationSignal' ),
        // this legacy operation does not seem to have any corresponding eZ5 signal...
        //'content_removetranslation' => '',
        'content_sort' => array( 'pre' => false, 'post' => 'LocationService\UpdateLocationSignal' ),
        'content_swap' => array( 'pre' => false, 'post' => 'LocationService\SwapLocationSignal' ),
        'content_updatealwaysavailable' => array( 'pre' => false, 'post' => 'ContentService\UpdateContentMetadataSignal' ),
        'content_updateinitiallanguage' => array( 'pre' => false, 'post' => 'ContentService\UpdateContentMetadataSignal' ),
        'content_updatemainassignment' => array( 'pre' => false, 'post' => 'ContentService\UpdateContentMetadataSignal' ),
        // this legacy operation needs hackish workarounds to retrieve the modified state(s), needed for the corresponding signal
        //'content_updateobjectstate' => 'ObjectStateService\SetContentStateSignal' ),
        'content_updatepriority' => array( 'pre' => false, 'post' => 'LocationService\UpdateLocationSignal' ),
        'content_updatesection' => array( 'pre' => false, 'post' => 'SectionService\AssignSectionSignal' ),
    );

    /**
     * @todo we should hook to 'after anything' only instead of just 'anything' for known signals, as they are all of type 'after'
     */
    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezworkflowengine/eventtypes', 'Run WorkFlowEngineBundle workflows' ) );
        $this->setTriggerTypes( array( '*' => true ) );
    }

    public function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $triggerName = reset( explode( '_', $parameters['trigger_name'], 2 ) );
        $operationName = $parameters['module_name'] . '_' . $parameters['module_function'];

        $signalName = $this->getsignalName( $triggerName, $operationName );
        if ( !$signalName )
        {
            eZDebug::writeError( "Trigger '$triggerName $operationName' can not be mapped to eZ5 workflow signal. Aborting eZ4 workflow" );
            return eZWorkflowType::STATUS_REJECTED;
        }

        $signalsParameters = $this->convertParameters( $triggerName, $operationName, $parameters );

        if ( !$signalsParameters )
        {
            eZDebug::writeNotice( "Parameters for trigger '$triggerName $operationName' do not map to an eZ5 workflow. Continuing the eZ4 workflow" );
            // this is a way to gracefully avoid triggering eZ5 workflows without failing the ez4 one
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        foreach ($signalsParameters as $signalParameters)
        {
            if ( !is_array( $signalParameters ) || ! count( $signalParameters ) )
            {
                eZDebug::writeError( "Parameters for trigger '$triggerName $operationName' can not be mapped to eZ5 workflow parameters. Aborting eZ4 workflow" );
                return eZWorkflowType::STATUS_REJECTED;
            }

            $signalParameters['legacyTrigger'] = $triggerName;
            $signalParameters['legacyOperation'] = $operationName;

            $serviceContainer = ezpKernel::instance()->getServiceContainer();
            $workflowService = $serviceContainer->get( 'ez_workflowengine_bundle.workflow_service' );

            try
            {
                eZDebug::writeDebug( "Triggering any eZ5 workflows available for signal '$signalName' with parameters:" .
                    preg_replace( "/\n+/s", ' ', preg_replace('/^(Array| +|\(|\))/m', '', print_r( $signalParameters, true ) ) ),
                    __METHOD__
                );
                $workflowService->triggerWorkflow( $signalName, $signalParameters );
            } catch ( \Exception $e )
            {
                eZDebug::writeError( $e->getMessage(), __METHOD__ );
                return eZWorkflowType::STATUS_REJECTED;
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }

    /**
     * Returns the eZ5 Signal known to map to the eZ4 Operation. If mapping is unknown, the eZ4 Operation name is returned
     * @param string $triggerName
     * @param string $operationName
     * @return string mixed
     */
    protected function getsignalName( $triggerName, $operationName )
    {
        if ( !isset( self::$signalMapping[$operationName][$triggerName] ) )
        {
            return $triggerName . '_' . $operationName;
        }

        return self::$signalMapping[$operationName][$triggerName];
    }

    /**
     * @param $triggerName
     * @param $operationName
     * @param array $parameters
     * @return array[] Each element is an array of parameters, used to trigger an ez5 workflow. Return array( false )
     *                 for error conditions and array() to ignore ez5 workflows but continue the ez4 one
     */
    protected function convertParameters( $triggerName, $operationName, array $parameters )
    {
        /// @see https://doc.ez.no/display/EZP/Signals+reference
        switch( $operationName )
        {
            case 'content_addlocation':
                $out = array();
                // loop over all locations of content and find the ones which are children of the parents from the trigger
                // NB: will break if eZ allows to create 2 locations in the same place for a single content...
                $locations = eZContentObjectTreeNode::fetchByContentObjectID($parameters['object_id']);
                foreach( $parameters['select_node_id_array'] as $parentNodeId )
                {
                    foreach( $locations as $contentLocation )
                    {
                        if ( $contentLocation->attribute( 'parent_node_id' ) == $parentNodeId )
                        {
                            $out[] = array(
                                'contentId' => $parameters['object_id'],
                                /// @todo grab location id of created node
                                'locationId' => $contentLocation->attribute( 'node_id' ),
                            );
                            break;
                        }
                    }
                }

                return $out;

            /*case 'content_delete':
                /// @todo this only works on BEFORE trigger (or when using trash): we get a list of nodes and need to find the object...
                return array(
                    'contentId' => $parameters['node_id']
                );*/

            case 'content_hide':
                $objectId = $this->objectIdFromNodeId($parameters['node_id']);
                if ( !$objectId )
                {
                    return array( false );
                }
                return array( array(
                    'locationId' => $parameters['node_id'],
                    'contentId' => $objectId,
                ) );

            case 'content_move':
                return array( array(
                    'locationId' => $parameters['node_id'],
                    'newParentLocationId' => $parameters['new_parent_node_id'],
                ) );

            case 'content_publish':
                return array( array(
                    'contentId' => $parameters['object_id'],
                    'versionNo' => $parameters['version'],
                ) );

            /*case 'content_removelocation':
                $out = array();
                foreach( $parameters['node_list'] as $nodeId )
                {
                    if ( is_object( $nodeId ) )
                    {
                        $node = $nodeId;
                        $nodeId = $node->attribute( 'node_id' );
                    }
                    else
                    {
                        // note: this will most likely fail, as we get the node ids of nodes just removed...
                        $node = \eZContentObjectTreeNode::fetch( $nodeId );
                        if ( !$node )
                        {
                            return array( false );
                        }
                    }
                    $object = $node->attribute( 'content' );
                    if ( !$object )
                    {
                        return array( false );
                    }
                    $objectId =  $object->attribute( 'id' );
                    $out[] = array(
                        'contentId' => $objectId,
                        'locationId' => $nodeId,
                    );
                }
                return $out;*/

            //case 'content_removetranslation':

            case 'content_sort':
                $objectId = $this->objectIdFromNodeId( $parameters['node_id'] );
                if ( !$objectId )
                {
                    return array( false );
                }
                return array( array(
                    'contentId' => $objectId,
                    'locationId' => $parameters['node_id'],
                ) );

            case 'content_swap':
                $objectId1 = $this->objectIdFromNodeId( $parameters['node_id'] );
                $objectId2 = $this->objectIdFromNodeId( $parameters['selected_node_id'] );
                if ( !$objectId1 || !$objectId2 )
                {
                    return array( false );
                }
                return array( array(
                    'content1Id' => $objectId1,
                    'location1Id' => $parameters['node_id'],
                    'content2Id' => $objectId2,
                    'location2Id' => $parameters['selected_node_id'],
                ) );

            case 'content_updatemainassignment':
                return array( array(
                    'contentId' => $parameters['object_id'],
                ) );

            case 'content_updatepriority':
                $objectId = $this->objectIdFromNodeId($parameters['node_id']);
                if ( !$objectId )
                {
                    return array( false );
                }
                return array( array(
                    'contentId' => '',
                    'locationId' => $parameters['node_id'],
                ) );

            case 'content_updatesection':
                $objectId = $this->objectIdFromNodeId($parameters['node_id']);
                if ( !$objectId )
                {
                    return array( false );
                }
                return array( array(
                    'contentId' => $objectId,
                    'sectionId' => $parameters['selected_section_id'],
                ) );

            case 'content_updateinitiallanguage':
                return array( array(
                    'contentId' => $parameters['object_id'],
                ) );

            case 'content_updatealwaysavailable':
                return array( array(
                    'contentId' => $parameters['object_id'],
                ) );

            /*case 'content_updateobjectstate':
                /// @todo we get an array of all states, but the eZ5 event needs to know which one changed
                return array(
                    'contentId' => $parameters['object_id'],
                    'objectStateGroupId' => '',
                    'objectStateId' => '',
                );*/

            // in case of an unmapped legacy operation, we let through all parameters unmodified - except a few known ones
            // (is it really a good idea to remove the known params?)
            default:
                return array( array_diff_key( $parameters, array( 'workflow_id', 'trigger_name', 'module_name', 'module_function', 'user_id' ) ) );
        }
    }

    protected function objectIdFromNodeId( $nodeID )
    {
        $node = eZContentObjectTreeNode::fetch( $nodeID );
        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node ' . $nodeID, __METHOD__ );
            return null;
        }

        return $node->attribute( 'contentobject_id' );
    }
}

eZWorkflowEventType::registerEventType( eZWorkflowEngineHookType::WORKFLOW_TYPE_STRING, 'eZWorkflowEngineHookType' );
