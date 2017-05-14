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
                case 'LocationService\HideLocationSignal':
                    $this->referenceResolver->addReference('slot:location_id', $signal->locationId, true);
                    $this->referenceResolver->addReference('slot:content_id', $signal->contentId, true);
                    $this->referenceResolver->addReference('slot:current_version', $signal->currentVersionNo, true);
                default;
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
                    $slotName
                );

                $this->workflowService->executeWorkflow($wfd);
            }
        }
    }
}
