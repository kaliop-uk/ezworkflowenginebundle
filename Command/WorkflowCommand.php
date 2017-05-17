<?php

namespace Kaliop\eZWorkflowEngineBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Kaliop\eZMigrationBundle\API\Value\Migration;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;

/**
 * Command to manipulate existing workflows.
 */
class WorkflowCommand extends AbstractCommand
{
    /**
     * Set up the command.
     *
     * Define the name, options and help text.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('kaliop:workflows:workflow')
            ->setDescription('Manually delete workflows from the database table.')
            ->addOption('delete', null, InputOption::VALUE_NONE, "Delete the specified workflow.")
            ->addOption('info', null, InputOption::VALUE_NONE, "Get info about the specified workflow.")
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, "Do not ask any interactive question.")
            ->addArgument('workflow', InputArgument::REQUIRED, 'The workflow to view or delete (plain workflow name).', null)
            ->setHelp(<<<EOT
The <info>kaliop:workflows:workflow</info> command allows you to manually delete dead workflows from the database table:

    <info>./ezpublish/console kaliop:workflows:workflow --delete workflow_name</info>

As well as viewing details:

    <info>./ezpublish/console kaliop:workflows:workflow --info workflow_name</info>
EOT
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('delete') && !$input->getOption('info')) {
            throw new \InvalidArgumentException('You must specify whether you want to --delete or --info the specified workflow.');
        }

        $workflowService = $this->getWorkflowService();
        $workflowNameOrPath = $input->getArgument('workflow');

        if ($input->getOption('info')) {
            $output->writeln('');

            $workflow = $workflowService->getWorkflow($workflowNameOrPath);
            if ($workflow == null) {
                throw new \InvalidArgumentException(sprintf('The workflow "%s" does not exist in the database table.', $workflowNameOrPath));
            }

            switch ($workflow->status) {
                case Migration::STATUS_DONE:
                    $status = '<info>executed</info>';
                    break;
                case Migration::STATUS_STARTED:
                    $status = '<comment>execution started</comment>';
                    break;
                case Migration::STATUS_TODO:
                    // bold to-migrate!
                    $status = '<error>not executed</error>';
                    break;
                case Migration::STATUS_SKIPPED:
                    $status = '<comment>skipped</comment>';
                    break;
                case Migration::STATUS_PARTIALLY_DONE:
                    $status = '<comment>partially executed</comment>';
                    break;
                case Migration::STATUS_SUSPENDED:
                    $status = '<comment>suspended</comment>';
                    break;
                case Migration::STATUS_FAILED:
                    $status = '<error>failed</error>';
                    break;
            }

            $output->writeln('<info>Workflow: ' . $workflow->name . '</info>');
            $output->writeln('Status: ' . $status);
            $output->writeln('Executed on: <info>' . ($workflow->executionDate != null ? date("Y-m-d H:i:s", $workflow->executionDate) : '--'). '</info>');
            $output->writeln('Execution notes: <info>' . $workflow->executionError . '</info>');

            if ($workflow->status == Migration::STATUS_SUSPENDED) {
                /// @todo decode the suspension context: date, step, ...
            }

            $output->writeln('Definition path: <info>' . $workflow->path . '</info>');
            $output->writeln('Definition md5: <info>' . $workflow->md5 . '</info>');

            if ($workflow->path != '') {
                // q: what if we have a loader which does not work with is_file? We could probably remove this check...
                if (is_file($workflow->path)) {
                    try {
                        $workflowDefinitionCollection = $workflowService->getWorkflowsDefinitions(array($workflow->path));
                        if (count($workflowDefinitionCollection)) {
                            $workflowDefinition = reset($workflowDefinitionCollection);
                            $workflowDefinition = $workflowService->parseWorkflowDefinition($workflowDefinition);

                            if ($workflowDefinition->status != MigrationDefinition::STATUS_PARSED) {
                                $output->writeln('Definition error: <error>' . $workflowDefinition->parsingError . '</error>');
                            }

                            if (md5($workflowDefinition->rawDefinition) != $workflow->md5) {
                                $output->writeln('Notes: <comment>The workflow definition file has now a different checksum</comment>');
                            }
                        } else {
                            $output->writeln('Definition error: <error>The workflow definition file can not be loaded</error>');
                        }
                    } catch (\Exception $e) {
                        /// @todo one day we should be able to limit the kind of exceptions we have to catch here...
                        $output->writeln('Definition parsing error: <error>' . $e->getMessage() . '</error>');
                    }
                } else {
                    $output->writeln('Definition error: <error>The workflow definition file can not be found any more</error>');
                }
            }

            $output->writeln('');
            return;
        }

        // ask user for confirmation to make changes
        if ($input->isInteractive() && !$input->getOption('no-interaction')) {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation(
                $output,
                '<question>Careful, the database will be modified. Do you want to continue Y/N ?</question>',
                false
            )
            ) {
                $output->writeln('<error>Workflow change cancelled!</error>');
                return 0;
            }
        }

        if ($input->getOption('delete')) {
            $workflow = $workflowService->getWorkflow($workflowNameOrPath);
            if ($workflow == null) {
                throw new \InvalidArgumentException(sprintf('The workflow "%s" does not exist in the database table.', $workflowNameOrPath));
            }

            $workflowService->deleteWorkflow($workflow);

            return;
        }
    }
}
