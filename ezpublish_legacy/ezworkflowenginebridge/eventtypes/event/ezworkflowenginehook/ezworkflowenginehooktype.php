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
        'content_removelocation' => array( 'pre' => false, 'post' => 'LocationService\DeleteLocationSignal' ),
        'content_sort' => array( 'pre' => false, 'post' => 'LocationService\UpdateLocationSignal' ),
        'content_swap' => array( 'pre' => false, 'post' => 'LocationService\SwapLocationSignal' ),
        'content_updatealwaysavailable' => array( 'pre' => false, 'post' => 'ContentService\UpdateContentMetadataSignal' ),
        'content_updateinitiallanguage' => array( 'pre' => false, 'post' => 'ContentService\UpdateContentMetadataSignal' ),
        'content_updatemainassignment' => array( 'pre' => false, 'post' => 'ContentService\UpdateContentMetadataSignal' ),
        // this legacy operation needs hackish workarounds to retrieve the modified state(s), needed for the corresponding signal
        //'content_updateobjectstate' => 'ObjectStateService\SetContentStateSignal' ),
        'content_updatepriority' => array( 'pre' => false, 'post' => 'LocationService\UpdateLocationSignal' ),
        'content_updatesection' => array( 'pre' => false, 'post' => 'SectionService\AssignSectionSignal' ),
        // this legacy operation does not seem to have any corresponding eZ5 signal...
        //'content_removetranslation' => '',
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
        $triggerName = reset(explode('_', $parameters['trigger_name'], 2));
        $operationName = $parameters['module_name'] . '_' . $parameters['module_function'];

        $signalName = $this->getsignalName( $triggerName, $operationName );
        if ( !$signalName ) {
            eZDebug::writeError( "Trigger '$triggerName $operationName' can not be mapped to eZ5 workflow signal" );
            return eZWorkflowType::STATUS_REJECTED;
        }

        $signalParameters = $this->convertParameters( $triggerName, $operationName, $parameters );
        if ( !$signalParameters ) {
            eZDebug::writeError( "Parameters for trigger '$triggerName $operationName' can not be mapped to eZ5 workflow parameters" );
            return eZWorkflowType::STATUS_REJECTED;
        }

        $signalParameters['legacyTrigger'] = $triggerName;
        $signalParameters['legacyOperation'] = $operationName;

        $serviceContainer = ezpKernel::instance()->getServiceContainer();
        $workflowTriggerSlot = $serviceContainer->get( 'ez_workflowengine_bundle.slot.workflowtrigger' );

        try {
            $workflowTriggerSlot->triggerWorkflow( $signalName, $signalParameters );
        } catch (\Exception $e) {
            eZDebug::writeError($e->getMessage(), __METHOD__);
            return eZWorkflowType::STATUS_REJECTED;
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
        if (!isset(self::$signalMapping[$operationName][$triggerName])) {
            return $triggerName . '_' . $operationName;
        }

        return self::$signalMapping[$operationName][$triggerName];
    }

    protected function convertParameters( $triggerName, $operationName, array $parameters )
    {
        /// @see https://doc.ez.no/display/EZP/Signals+reference
        switch( $operationName ) {
            case 'content_addlocation':
                return array(
                    'contentId' => $parameters['object_id'],
                    /// @todo grab location id of created node
                    'locationId' => '',
                );

            /*case 'content_delete':
                /// @todo this only works on BEFORE trigger (or when using trash): we get a list of nodes and need to find the object...
                return array(
                    'contentId' => $parameters['node_id']
                );*/

            case 'content_hide':
                $objectId = $this->objectIdFromNodeId($parameters['node_id']);
                if ( !$objectId ) {
                    return false;
                }
                return array(
                    'locationId' => $parameters['node_id'],
                    'contentId' => $objectId,
                );

            case 'content_move':
                return array(
                    'locationId' => $parameters['node_id'],
                    'newParentLocationId' => $parameters['new_parent_node_id'],
                );

            case 'content_publish':
                return array(
                    'contentId' => $parameters['object_id'],
                    'versionNo' => $parameters['version'],
                );

            case 'content_removelocation':
                /// @todo we get an array, but generate only signal 1 removal!
                $nodeId = reset( $parameters['node_list'] );
                if ( is_object( $nodeId ) ) {
                    $nodeId = $nodeId->attribute( 'node_id' );
                }
                return array(
                    'contentId' => '',
                    'locationId' => $nodeId,
                );

            case 'content_sort':
                $objectId = $this->objectIdFromNodeId( $parameters['node_id'] );
                if ( !$objectId ) {
                    return false;
                }
                return array(
                    'contentId' => $objectId,
                    'locationId' => $parameters['node_id'],
                );

            case 'content_swap':
                $objectId1 = $this->objectIdFromNodeId( $parameters['node_id'] );
                $objectId2 = $this->objectIdFromNodeId( $parameters['selected_node_id'] );
                if ( !$objectId1 || !$objectId2 ) {
                    return false;
                }
                return array(
                    'content1Id' => $objectId1,
                    'location1Id' => $parameters['node_id'],
                    'content2Id' => $objectId2,
                    'location2Id' => $parameters['selected_node_id'],
                );

            case 'content_updatemainassignment':
                return array(
                    'contentId' => $parameters['object_id'],
                );

            case 'content_updatepriority':
                $objectId = $this->objectIdFromNodeId($parameters['node_id']);
                if ( !$objectId ) {
                    return false;
                }
                return array(
                    'contentId' => '',
                    'locationId' => $parameters['node_id'],
                );

            case 'content_updatesection':
                $objectId = $this->objectIdFromNodeId($parameters['node_id']);
                if ( !$objectId ) {
                    return false;
                }
                return array(
                    'contentId' => $objectId,
                    'sectionId' => $parameters['selected_section_id'],
                );

            case 'content_updateinitiallanguage':
                return array(
                    'contentId' => $parameters['object_id'],
                );

            case 'content_updatealwaysavailable':
                return array(
                    'contentId' => $parameters['object_id'],
                );

            //case 'content_removetranslation':

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
                return array_diff_key($parameters, array('workflow_id', 'trigger_name', 'module_name', 'module_function', 'user_id'));
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
