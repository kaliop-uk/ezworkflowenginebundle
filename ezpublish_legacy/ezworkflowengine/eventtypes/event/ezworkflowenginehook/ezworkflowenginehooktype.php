<?php

class eZWorkflowEngineHookType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezworkflowenginehook';

    static $slotMapping = array(
        'content_addlocation' => 'LocationService\CreateLocationSignal',
        'content_delete' => 'ContentService\DeleteContentSignal',
        'content_hide' => 'LocationService\HideLocationSignal',
        'content_move' => 'LocationService\MoveSubtreeSignal',
        'content_publish' => 'ContentService\PublishVersionSignal',
        'content_removelocation' => 'LocationService\DeleteLocationSignal',
        'content_sort' => 'LocationService\UpdateLocationSignal',
        'content_swap' => 'LocationService\SwapLocationSignal',
        'content_updatealwaysavailable' => 'ContentService\UpdateContentMetadataSignal',
        'content_updateinitiallanguage' => 'ContentService\UpdateContentMetadataSignal',
        'content_updatemainassignment' => 'ContentService\UpdateContentMetadataSignal',
        'content_updateobjectstate' => 'ObjectStateService\SetContentStateSignal',
        'content_updatepriority' => 'LocationService\UpdateLocationSignal',
        'content_updatesection' => 'SectionService\AssignSectionSignal',
        // this legacy action does not seem to mapped onto a corresponding eZ5 signal...
        //'content_removetranslation' => 'LocationService\HideLocationSignal',
    );

    /**
     * @todo we should hook to 'after anything' only instead of just 'anything', as ez5 signals are all of type 'after'
     */
    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezworkflowengine/eventtypes', 'Hook WorkFlowEngineBundle workflows' ) );
        $this->setTriggerTypes( array( '*' => true ) );
    }

    public function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $actionName = $parameters['module_name'] . '_' . $parameters['module_function'];
        $slotName = $this->getSlotName( $actionName );
        if (!$slotName) {
            eZDebug::writeError("Trigger action '$actionName' can not be mapped to eZ5 workflow signal");
            return eZWorkflowType::STATUS_REJECTED;
        }

        $signalParameters = $this->convertParameters( $actionName, $parameters );
        if (!$signalParameters) {
            eZDebug::writeError("Parameters for trigger action '$actionName' can not be mapped to eZ5 signal");
            return eZWorkflowType::STATUS_REJECTED;
        }

        $signalParameters['legacyAction'] = $actionName;

        $serviceContainer = ezpKernel::instance()->getServiceContainer();
        $workflowTriggerSlot = $serviceContainer->get( 'ez_workflowengine_bundle.slot.workflow' );

        try {
            $workflowTriggerSlot->triggerWorkflow( $slotName, $signalParameters );
        } catch (\Exception $e) {
            eZDebug::writeError($e->getMessage(), __METHOD__);
            return eZWorkflowType::STATUS_REJECTED;
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }

    protected function getSlotName( $actionName )
    {
        if (!isset(self::$slotMapping[$actionName])) {
            return false;
        }

        return self::$slotMapping[$actionName];
    }

    protected function convertParameters( $actionName, array $parameters )
    {
        /// @see https://doc.ez.no/display/EZP/Signals+reference
        switch( $actionName ) {
            case 'content_addlocation':
                return array(
                    'contentId' => $parameters['object_id'],
                    /// @todo grab location id of created node
                    'locationId' => '',
                );

            case 'content_delete':
                /// @todo this only work on BEFORE trigger (or when using trash): we get a list of nodes and need to find the object...
                return array(
                    'contentId' => $parameters['node_id']
                );

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

            case 'content_updateobjectstate':
                /// @todo we get an array of all states, but the eZ5 event needs to know which one changed
                return array(
                    'contentId' => $parameters['object_id'],
                    'objectStateGroupId' => '',
                    'objectStateId' => '',
                );
        }

        return false;
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
