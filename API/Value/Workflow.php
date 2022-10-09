<?php

namespace Kaliop\eZWorkflowEngineBundle\API\Value;

use Kaliop\eZMigrationBundle\API\Value\Migration;

/**
 * @property-read string $signalName
 */
class Workflow extends Migration
{
    protected $signalName;

    public function __construct($name, $md5, $path, $executionDate = null, $status = 0, $executionError = null, $signalName = null)
    {
        $this->name = $name;
        $this->md5 = $md5;
        $this->path = $path;
        $this->executionDate = $executionDate;
        $this->status = $status;
        $this->executionError = $executionError;
        $this->signalName = $signalName;
    }
}
