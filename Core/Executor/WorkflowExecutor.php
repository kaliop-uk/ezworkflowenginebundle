<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\Executor;

use Kaliop\eZMigrationBundle\Core\Executor\MigrationExecutor;

class WorkflowExecutor extends MigrationExecutor
{
    protected $supportedStepTypes = array('workflow');

    /**
     * Add support for cancel/unless as handy shortcut for cancel/if/not
     *
     * @param array $dsl
     * @param array $context
     * @return bool|true
     */
    protected function cancel($dsl, $context)
    {
        if (isset($dsl['unless'])) {
            if ($this->matchConditions($dsl['unless'])) {
                // q: return timestamp, matched condition or ... ?
                return true;
            }
        }

        return parent::cancel($dsl, $context);
    }
}
