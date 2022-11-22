<?php

namespace Kaliop\eZWorkflowEngineBundle\Command;

use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to display the defined workflow definitions.
 */
class DebugCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('kaliop:workflows:debug')
            ->setDescription('List the configured workflow definitions')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "The directory or file to load the workflow definitions from")
            ->addOption('show-path', null, InputOption::VALUE_NONE, "Print definition path instead of notes")
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workflowService = $this->getWorkflowService();

        $displayPath = $input->getOption('show-path');

        $workflowDefinitions = $workflowService->getWorkflowsDefinitions($input->getOption('path'));

        if (!count($workflowDefinitions)) {
            $output->writeln('<info>No workflow definitions found</info>');
            return 0;
        }

        $i = 1;
        foreach ($workflowDefinitions as $workflowDefinition) {
            switch ($workflowDefinition->status) {
                case MigrationDefinition::STATUS_INVALID:
                    $name = '<error>' . $workflowDefinition->name . '</error>';
                    break;
                default:
                    $name = $workflowDefinition->name;
            }
            $data[] = array(
                $i++,
                $name,
                $workflowDefinition->signalName,
                //$workflowDefinition->path,
                ($workflowDefinition->runAs === false) ? '-' : $workflowDefinition->runAs,
                $workflowDefinition->useTransaction ? 'Y' : 'N',
                $displayPath ? $workflowDefinition->path : $workflowDefinition->parsingError
            );

        }

        $table = new Table($output);
        $table
            ->setHeaders(array('#', 'Workflow definition', 'Signal', 'Switch user', 'Use transaction', /*'Path',*/ 'Notes'))
            ->setRows($data);
        $table->render();

        return 0;
    }
}
