<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\Slot;

use eZ\Publish\Core\SignalSlot\Slot;
use eZ\Publish\Core\SignalSlot\Signal;
use Kaliop\eZMigrationBundle\API\ReferenceBagInterface;
use Kaliop\eZWorkflowEngineBundle\API\Value\WorkflowDefinition;

class WorkflowTrigger extends Slot
{
    protected $workflowService;
    protected $referenceResolver;

    public function __construct($workflowService, ReferenceBagInterface $referenceResolver)
    {
        $this->workflowService = $workflowService;
        $this->referenceResolver = $referenceResolver;
    }

    public function receive(Signal $signal)
    {
        $className = get_class($signal);
        $slotName = str_replace('eZ\Publish\Core\SignalSlot\Signal\\', '', $className);

        $workflowDefinitions = $this->workflowService->getValidWorkflowsDefinitionsForSlot($slotName);

        if (count($workflowDefinitions)) {
            switch($slotName) {
                case 'ContentService\AddRelationSignal':
                case 'ContentService\DeleteRelationSignal':
                    $this->referenceResolver->addReference('slot:src_content_id', $signal->srcContentId, true);
                    $this->referenceResolver->addReference('slot:src_version_no', $signal->srcVersionNo, true);
                    $this->referenceResolver->addReference('slot:dst_content_id', $signal->dstContentId, true);
                    break;
                case 'ContentService\CreateContentSignal':
                case 'ContentService\DeleteVersionSignal':
                case 'ContentService\PublishVersionSignal':
                case 'ContentService\UpdateContentSignal':
                case 'ContentService\UpdateContentMetadataSignal':
                    $this->referenceResolver->addReference('slot:content_id', $signal->contentId, true);
                    $this->referenceResolver->addReference('slot:version_no', $signal->versionNo, true);
                    break;
                case 'ContentService\CopyContentSignal':
                    $this->referenceResolver->addReference('slot:src_content_id', $signal->srcContentId, true);
                    $this->referenceResolver->addReference('slot:src_version_no', $signal->srcVersionNo, true);
                    $this->referenceResolver->addReference('slot:dst_content_id', $signal->dstContentId, true);
                    $this->referenceResolver->addReference('slot:dst_version_no', $signal->dstVersionNo, true);
                    $this->referenceResolver->addReference('slot:dst_parent_location_id', $signal->dstParentLocationId , true);
                    break;
                case 'ContentService\CreateContentDraftSignal':
                case 'ContentService\TranslateVersionSignal':
                    $this->referenceResolver->addReference('slot:content_id', $signal->contentId, true);
                    $this->referenceResolver->addReference('slot:version_no', $signal->versionNo, true);
                    $this->referenceResolver->addReference('slot:user_id', $signal->$userId, true);
                    break;
                case 'ContentService\DeleteContentSignal':
                    $this->referenceResolver->addReference('slot:content_id', $signal->contentId, true);
                    break;

                case 'LocationService\CopySubtreeSignal':
                    $this->referenceResolver->addReference('slot:subtree_id', $signal->subtreeId, true);
                    $this->referenceResolver->addReference('slot:target_parent_location_id', $signal->targetParentLocationId, true);
                    break;
                case 'LocationService\CreateLocationSignal':
                case 'LocationService\DeleteLocationSignal':
                case 'LocationService\UpdateLocationSignal':
                    $this->referenceResolver->addReference('slot:content_id', $signal->contentId, true);
                    $this->referenceResolver->addReference('slot:location_id', $signal->locationId, true);
                    break;
                case 'LocationService\HideLocationSignal':
                case 'LocationService\UnhideLocationSignal':
                    $this->referenceResolver->addReference('slot:content_id', $signal->contentId, true);
                    $this->referenceResolver->addReference('slot:location_id', $signal->locationId, true);
                    $this->referenceResolver->addReference('slot:current_version', $signal->currentVersionNo, true);
                    break;
                case 'LocationService\MoveSubtreeSignal':
                    $this->referenceResolver->addReference('slot:subtree_id', $signal->subtreeId, true);
                    $this->referenceResolver->addReference('slot:new_parent_location_id', $signal->newParentLocationId, true);
                    break;
                case 'LocationService\SwapLocationSignal':
                    $this->referenceResolver->addReference('slot:content1_id', $signal->content1Id, true);
                    $this->referenceResolver->addReference('slot:location1_id', $signal->location1Id, true);
                    $this->referenceResolver->addReference('slot:content2_id', $signal->content2Id, true);
                    $this->referenceResolver->addReference('slot:location2_id', $signal->location2Id, true);
                    break;

                case 'ObjectStateService\SetContentStateSignal':
                    $this->referenceResolver->addReference('slot:content_id', $signal->contentId, true);
                    $this->referenceResolver->addReference('slot:object_state_group_id', $signal->objectStateGroupId, true);
                    $this->referenceResolver->addReference('slot:object_state_id', $signal->objectStateId, true);
                    break;

                case 'SectionService\AssignSectionSignal':
                    $this->referenceResolver->addReference('slot:content_id', $signal->contentId, true);
                    $this->referenceResolver->addReference('slot:section_id', $signal->sectionId, true);
                    break;
                case 'SectionService\CreateSectionSignal':
                case 'SectionService\DeleteSectionSignal':
                case 'SectionService\UpdateSectionSignal':
                    $this->referenceResolver->addReference('slot:section_id', $signal->sectionId, true);
                    break;

                case 'UserService\AssignUserToUserGroupSignal':
                case 'UserService\UnAssignUserFromUserGroupSignal':
                    $this->referenceResolver->addReference('slot:user_id', $signal->userId, true);
                    $this->referenceResolver->addReference('slot:user_group_id', $signal->userGroupId, true);
                    break;
                case 'UserService\CreateUserGroupSignal':
                case 'UserService\DeleteUserGroupSignal':
                case 'UserService\UpdateUserGroupSignal':
                    $this->referenceResolver->addReference('slot:user_group_id', $signal->userGroupId, true);
                    break;
                case 'UserService\CreateUserSignal':
                case 'UserService\DeleteUserSignal':
                case 'UserService\UpdateUserSignal':
                    $this->referenceResolver->addReference('slot:user_id', $signal->userId, true);
                    break;
                case 'UserService\MoveUserGroupSignal':
                    $this->referenceResolver->addReference('slot:user_group_id', $signal->userGroupId, true);
                    $this->referenceResolver->addReference('slot:new_parent_id', $signal->newParentId, true);
                    break;

                // since we listen to all eZP signals, this exception too is dangerous
                //default:
                //    throw new \Exception("Unsupported slot '$slotName'");
            }

            /** @var WorkflowDefinition $workflowDefinition */
            foreach ($workflowDefinitions as $workflowDefinition) {
                $wfd = new WorkflowDefinition(
                    $workflowDefinition->name . '/' . time() . '_' . getmypid(),
                    $workflowDefinition->path,
                    $workflowDefinition->rawDefinition,
                    $workflowDefinition->status,
                    $workflowDefinition->steps->getArrayCopy(),
                    null,
                    $slotName,
                    $workflowDefinition->runAs
                );

                /// @todo allow setting of userTransaction, default lang ?
                $this->workflowService->executeWorkflow($wfd, true, null, $workflowDefinition->runAs);
            }
        }
    }
}
