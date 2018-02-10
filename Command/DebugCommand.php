<?php

namespace Kaliop\eZWorkflowEngineBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Kaliop\eZMigrationBundle\API\Value\Migration;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;

/**
 * Command to display the defined workflow definitions.
 */
class DebugCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('kaliop:workflows:debug')
            ->setDescription('List the configured workflow definitions')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "The directory or file to load the workflow definitions from"
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workflowService = $this->getWorkflowService();

        $workflowDefinitions = $workflowService->getWorkflowsDefinitions($input->getOption('path'));

        if (!count($workflowDefinitions)) {
            $output->writeln('<info>No workflow definitions found</info>');
            return;
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
                $workflowDefinition->parsingError
            );

        }

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('#', 'Workflow definition', 'Signal', 'Switch user', 'Use transaction', /*'Path',*/ 'Notes'))
            ->setRows($data);
        $table->render($output);
    }
}
