<?php

namespace Kaliop\eZWorkflowEngineBundle\API\Value;

use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;

/**
 * @property-read string $signalName
 * @property-read string $runAs use false for 'current user'
 * @property-read bool $useTransaction
 * @property-read bool $avoidRecursion
 */
class WorkflowDefinition extends MigrationDefinition
{
    const MANIFEST_STEP_TYPE= 'workflow';
    const MANIFEST_SIGNAL_ELEMENT= 'signal';
    const MANIFEST_RUNAS_ELEMENT= 'run_as';
    const MANIFEST_USETRANSACTION_ELEMENT= 'transaction';
    const MANIFEST_AVOIDRECURSION_ELEMENT= 'avoid_recursion';

    protected $signalName;
    // unlike migrations, workflows default to run as current user
    protected $runAs = false;
    protected $useTransaction = false;
    protected $avoidRecursion;

    /**
     * WorkflowDefinition constructor.
     * @param string $name
     * @param string $path
     * @param string $rawDefinition
     * @param int $status
     * @param \Kaliop\eZMigrationBundle\API\Value\MigrationStep[]|\Kaliop\eZMigrationBundle\API\Collection\MigrationStepsCollection $steps
     * @param string $parsingError
     * @param string $signalName
     * @param string|int|false|null $runAs if false will use the current user; if null will use hardcoded 14; string for login or user id
     * @param bool $useTransaction
     * @throws \Exception
     */
    public function __construct($name, $path, $rawDefinition, $status = 0, $steps = array(), $parsingError = null,
        $signalName = null, $runAs = false, $useTransaction = false, $avoidRecursion = false)
    {
        if ($status == MigrationDefinition::STATUS_PARSED && $signalName == null) {
            throw new \Exception("Can not create a parsed Workflow definition without a corresponding signal");
        }

        $this->signalName = $signalName;
        $this->runAs = $runAs;
        $this->useTransaction = $useTransaction;
        $this->avoidRecursion = $avoidRecursion;

        parent::__construct(
            $name, $path, $rawDefinition, $status, $steps, $parsingError
        );
    }

    /**
     * Allow the class to be serialized to php using var_export
     * @param array $data
     * @return static
     */
    public static function __set_state(array $data)
    {
        return new static(
            $data['name'],
            $data['path'],
            $data['rawDefinition'],
            $data['status'],
            $data['steps'],
            $data['parsingError'],
            $data['signalName'],
            $data['runAs'],
            $data['useTransaction'],
            $data['avoidRecursion']
        );
    }
}
