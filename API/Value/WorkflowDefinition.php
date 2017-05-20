<?php

namespace Kaliop\eZWorkflowEngineBundle\API\Value;

use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;

/**
 * @property-read string $signalName
 * @property-read string $runAs
 */
class WorkflowDefinition extends MigrationDefinition
{
    const NAME_PREFIX = 'workflow://';
    const MANIFEST_STEP_TYPE= 'workflow';
    const MANIFEST_SIGNAL_ELEMENT= 'signal';
    const MANIFEST_RUNAS_ELEMENT= 'run_as';

    protected $signalName;
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
     * @param string $signalName
     * @param string|int|false|null $runAs if false will use the current user; if null will use hardcoded 14; string for login or user id
     * @throws \Exception
     */
    public function __construct($name, $path, $rawDefinition, $status = 0, array $steps = array(), $parsingError = null,
        $signalName = null, $runAs = false)
    {
        if (strpos($name, self::NAME_PREFIX) !== 0) {
            $name = self::NAME_PREFIX . $name;
        }

        if ($status == MigrationDefinition::STATUS_PARSED && $signalName == null) {
            throw new \Exception("Can not create a parsed Workflow definition without a corresponding signal");
        }
        $this->signalName = $signalName;

        $this->runAs = $runAs;

        parent::__construct(
            $name, $path, $rawDefinition, $status, $steps, $parsingError
        );
    }
}
