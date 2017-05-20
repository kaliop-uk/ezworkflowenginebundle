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
    static $workflowExecuting = 0;

    public function __construct($workflowService, ReferenceBagInterface $referenceResolver)
    {
        $this->workflowService = $workflowService;
        $this->referenceResolver = $referenceResolver;
    }

    public function receive(Signal $signal)
    {
        return $this->triggerWorkflow($this->signalNameFromSignal($signal), (array)$signal);
    }

    protected function signalNameFromSignal(Signal $signal)
    {
        $className = get_class($signal);
        return str_replace('eZ\Publish\Core\SignalSlot\Signal\\', '', $className);
    }

    /**
     * @param string $signalName must use the same format as we extract from signal class names
     * @param array $parameters must follow what is found in eZ5 signals
     * @throws \Exception
     */
    public function triggerWorkflow($signalName, array $parameters)
    {
        $workflowDefinitions = $this->workflowService->getValidWorkflowsDefinitionsForSignal($signalName);

        if (count($workflowDefinitions)) {

            foreach($parameters as $parameter => $value) {
                $this->referenceResolver->addReference('signal:' . $this->convertSignalMember($signalName, $parameter), $value, true);
            }

            /** @var WorkflowDefinition $workflowDefinition */
            foreach ($workflowDefinitions as $workflowDefinition) {

                /// q: is it correct to put this here, or should it be in workflowService->executeWorkflow() ?
                if (self::$workflowExecuting > 0 && $workflowDefinition->avoidRecursion) {
                    return;
                }

                $wfd = new WorkflowDefinition(
                    $workflowDefinition->name,
                    $workflowDefinition->path,
                    $workflowDefinition->rawDefinition,
                    $workflowDefinition->status,
                    $workflowDefinition->steps->getArrayCopy(),
                    null,
                    $signalName,
                    $workflowDefinition->runAs,
                    $workflowDefinition->useTransaction,
                    $workflowDefinition->avoidRecursion
                );

                self::$workflowExecuting += 1;
                try {
                    /// @todo allow setting of default lang ?
                    $this->workflowService->executeWorkflow($wfd, $workflowDefinition->useTransaction, null, $workflowDefinition->runAs);
                    self::$workflowExecuting -= 1;
                } catch (\Exception $e) {
                    self::$workflowExecuting -= 1;
                    throw $e;
                }
            }
        }
    }

    /**
     * @param string $signalName
     * @param string $parameter
     * @return string
     */
    protected function convertSignalMember($signalName, $parameter)
    {
        // CamelCase to snake_case using negative look-behind in regexp
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $parameter));
    }
}
