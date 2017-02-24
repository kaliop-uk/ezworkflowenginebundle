<?php

namespace Kaliop\eZWorkflowEngineBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Kaliop\eZMigrationBundle\API\Value\Migration;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;

/**
 * Command to display the defined workflows.
 */
class StatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('kaliop:workflows:status')
            ->setDescription('List the currently executing or already executed workflows')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workflowsService = $this->getWorkflowService();

        $workflows = $workflowsService->getWorkflows();

        if (!count($workflows)) {
            $output->writeln('<info>No workflows found</info>');
            return;
        }

        $i = 1;
        foreach ($workflows as $workflow) {
            switch ($workflow->status) {
                case Migration::STATUS_FAILED:
                    $name = '<error>' . $workflow->name . '</error>';
                    break;
                default:
                    $name = $workflow->name;
            }
            $data[] = array(
                $i++,
                $workflow->slotName,
                $name,
                $workflow->executionDate != null ? date("Y-m-d H:i:s", $workflow->executionDate) : '',
                $workflow->executionError,
            );

        }

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('#', 'Slot', 'Workflow', 'Executed on', 'Notes'))
            ->setRows($data);
        $table->render($output);
    }
}
