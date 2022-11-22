<?php

namespace Kaliop\eZWorkflowEngineBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Kaliop\eZMigrationBundle\API\Value\Migration;

/**
 * Command to clean up workflows.
 */
class CleanupCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('kaliop:workflows:cleanup')
            ->addOption('older-than', 'o', InputOption::VALUE_REQUIRED, "Only remove workflows which have finished since N minutes", 86400)
            ->addOption('failed', 'f',  InputOption::VALUE_NONE, "Remove failed instead of finished workflows")
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, "Only list workflows to remove, without actually doing it")
            ->setDescription('Removes old workflows from the list of executed ones')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $minAge = $input->getOption('older-than');

        $maxAge = time() - ($minAge * 60);
        $offset = 0;
        $limit = 1000;

        $workflowService = $this->getWorkflowService();
        $toRemove = array();
        $total = 0;
        do {
            $status = Migration::STATUS_DONE;
            $label = 'executed';
            if ($input->getOption('failed')) {
                $status = $status = Migration::STATUS_FAILED;
                $label = 'failed';
            }

            $workflows = $workflowService->getWorkflowsByStatus($status, $limit, $offset);

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

        if ($input->getOption('dry-run')) {
            $action = "To remove: ";

        } else {
            $action = "Removed ";
            foreach ($toRemove as $workflow) {
                $workflowService->deleteMigration($workflow);
            }
        }

        $output->writeln($action . count($toRemove) . " workflows out of $total $label");

        return 0;
    }
}
