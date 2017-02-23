<?php

namespace Kaliop\eZWorkflowEngineBundle\API\Value;

use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;

/**
 * @property-read string $slotName
 */
class WorkflowDefinition extends MigrationDefinition
{
    const NAME_PREFIX = 'workflow://';
    const MANIFEST_STEP_TYPE= 'workflow';
    const MANIFEST_SLOT_ELEMENT= 'slot';

    protected $slotName;

    public function __construct($name, $path, $rawDefinition, $status = 0, array $steps = array(), $parsingError = null, $slotName = null)
    {
        if (strpos($name, self::NAME_PREFIX) !== 0) {
            $name = self::NAME_PREFIX . $name;
        }

        if ($status == MigrationDefinition::STATUS_PARSED && $slotName == null) {
            throw new \Exception("Can not create a parsed Workflow definition without a corresponding slot");
        }
        $this->slotName = $slotName;

        parent::__construct(
            $name, $path, $rawDefinition, $status, $steps, $parsingError
        );
    }
}
