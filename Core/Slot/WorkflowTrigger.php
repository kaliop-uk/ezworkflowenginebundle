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
        return $this->triggerWorkflow($this->slotNameFromSignal($signal), (array)$signal);
    }

    protected function slotNameFromSignal(Signal $signal)
    {
        $className = get_class($signal);
        return str_replace('eZ\Publish\Core\SignalSlot\Signal\\', '', $className);
    }

    /**
     * @param string $slotName must use the same format as we extract from signal class names
     * @param array $parameters must follow what is found in eZ5 signals
     */
    public function triggerWorkflow($slotName, array $parameters)
    {
        $workflowDefinitions = $this->workflowService->getValidWorkflowsDefinitionsForSlot($slotName);

        if (count($workflowDefinitions)) {

            foreach($parameters as $parameter => $value) {
                $this->referenceResolver->addReference('slot:' . $this->convertSignalMember($slotName, $parameter), $value, true);
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

    /**
     * @param string $slotName
     * @param string $parameter
     * @return string
     */
    protected function convertSignalMember($slotName, $parameter)
    {
        // CamelCase to snake_case using negative look-behind in regexp
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $parameter));
    }
}
