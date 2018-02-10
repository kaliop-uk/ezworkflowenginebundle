<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\Slot;

use eZ\Publish\Core\SignalSlot\Slot;
use eZ\Publish\Core\SignalSlot\Signal;

class WorkflowTrigger extends Slot
{
    protected $workflowService;

    public function __construct($workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function receive(Signal $signal)
    {
        return $this->workflowService->triggerWorkflow($this->signalNameFromSignal($signal), (array)$signal);
    }

    protected function signalNameFromSignal(Signal $signal)
    {
        $className = get_class($signal);
        return str_replace('eZ\Publish\Core\SignalSlot\Signal\\', '', $className);
    }
}
