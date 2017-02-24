<?php

namespace Kaliop\eZWorkflowEngineBundle\API\Value;

use Kaliop\eZMigrationBundle\API\Value\Migration;

class Workflow extends Migration
{
    protected $slotName;

    public function __construct($name, $md5, $path, $executionDate = null, $status = 0, $executionError = null, $slotName = null)
    {
        $this->name = $name;
        $this->md5 = $md5;
        $this->path = $path;
        $this->executionDate = $executionDate;
        $this->status = $status;
        $this->executionError = $executionError;
        $this->slotName = $slotName;
    }
}