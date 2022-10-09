<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\EventListener;

use Psr\Log\LoggerInterface;
use Kaliop\eZMigrationBundle\Core\EventListener\TracingStepExecutedListener as BaseListener;

class TracingStepExecutedListener extends BaseListener
{
    protected $entity = 'workflow';
    protected $logger;

    public function __construct($enabled = true, LoggerInterface $logger = null)
    {
        parent::__construct($enabled);
        $this->logger = $logger;
    }

    /**
     * Unlike the parent class, we default to always writing to the log file and to the output only if available
     * @param string $out
     * @param int|null $verbosity
     */
    protected function echoMessage($out, $verbosity = null)
    {
        if ($this->output) {
            if ($this->output->getVerbosity() >= ($verbosity ? $verbosity : $this->minVerbosityLevel)) {
                $this->output->writeln($out);
            }
        }

        if ($this->logger) {
            $this->logger->debug($out);
        }
    }
}
