<?php

namespace Kaliop\eZWorkflowEngineBundle\API\Value;

use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;

/**
 * @property-read string $slotName
 * @property-read string $runAs
 */
class WorkflowDefinition extends MigrationDefinition
{
    const NAME_PREFIX = 'workflow://';
    const MANIFEST_STEP_TYPE= 'workflow';
    const MANIFEST_SLOT_ELEMENT= 'slot';
    const MANIFEST_RUNAS_ELEMENT= 'run_as';

    protected $slotName;
    // unlike migrations, workflows default to run as current user
    protected $runAs = false;

    /**
     * WorkflowDefinition constructor.
     * @param string $name
     * @param string $path
     * @param string $rawDefinition
     * @param int $status
     * @param array $steps
     * @param string $parsingError
     * @param string $slotName
     * @param string|int|false|null $runAs if false will use the current user; if null will use hardcoded 14; string for login or user id
     * @throws \Exception
     */
    public function __construct($name, $path, $rawDefinition, $status = 0, array $steps = array(), $parsingError = null,
        $slotName = null, $runAs = false)
    {
        if (strpos($name, self::NAME_PREFIX) !== 0) {
            $name = self::NAME_PREFIX . $name;
        }

        if ($status == MigrationDefinition::STATUS_PARSED && $slotName == null) {
            throw new \Exception("Can not create a parsed Workflow definition without a corresponding slot");
        }
        $this->slotName = $slotName;

        $this->runAs = $runAs;

        parent::__construct(
            $name, $path, $rawDefinition, $status, $steps, $parsingError
        );
    }
}
