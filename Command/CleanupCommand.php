<?php

namespace Kaliop\eZWorkflowEngineBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Kaliop\eZMigrationBundle\API\Value\Migration;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;

/**
 * Command to clean up workflows.
 */
class CleanupCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('kaliop:workflows:cleanup')
            ->addOption('older-than', null, InputOption::VALUE_REQUIRED, "Only remove workflows which have finished since N minutes", 86400)
            ->setDescription('Removes old workflows from the list of executed ones')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $minAge = $input->getOption('older-than');

        $maxAge = time() - ($minAge * 60);
        $offset = 0;
        $limit = 1000;

        $workflowsService = $this->getWorkflowService();
        $toRemove = array();
        $total = 0;
        do {
            $workflows = $workflowsService->getMigrationsByStatus(Migration::STATUS_DONE, $limit, $offset);

            if (!count($workflows)) {
                break;
            }

            foreach($workflows as $workflow) {
                if ($workflow->executionDate < $maxAge) {
                    $toRemove[] = $workflow;
                }
            }

            $total += count($workflows);
            $offset += $limit;
        } while(true);

        foreach ($toRemove as $workflow) {
            $workflowsService->deleteMigration($workflow);
        }

        $output->writeln("Removed " . count($toRemove) . " workflows out of $total executed");
    }
}
