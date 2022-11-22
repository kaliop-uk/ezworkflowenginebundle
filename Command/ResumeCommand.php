<?php

namespace Kaliop\eZWorkflowEngineBundle\Command;

use Kaliop\eZMigrationBundle\API\Value\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to resume suspended workflows.
 *
 * @todo add support for resuming a set based on path
 * @todo add support for the separate-process cli switch
 */
class ResumeCommand extends AbstractCommand
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
            ->setName('kaliop:workflows:resume')
            ->setDescription('Restarts any suspended workflows.')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, "Do not ask any interactive question.")
            ->addOption('no-transactions', 'u', InputOption::VALUE_NONE, "Do not use a repository transaction to wrap each workflow. Unsafe, but needed for legacy slot handlers")
            ->addOption('workflow', 'w', InputOption::VALUE_REQUIRED, 'A single workflow to resume (workflow name).', null)
            ->setHelp(<<<EOT
The <info>kaliop:workflows:resume</info> command allows you to resume any suspended workflow
EOT
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int null or 0 if everything went fine, or an error code
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        $this->getContainer()->get('ez_migration_bundle.step_executed_listener.tracing')->setOutput($output);

        $workflowService = $this->getWorkflowService();

        $workflowName = $input->getOption('workflow');
        if ($workflowName != null) {
            $suspendedWorkflow = $workflowService->getWorkflow($workflowName);
            if (!$suspendedWorkflow) {
                throw new \Exception("Workflow '$workflowName' not found");
            }
            if ($suspendedWorkflow->status != Migration::STATUS_SUSPENDED) {
                throw new \Exception("Workflow '$workflowName' is not suspended, can not resume it");
            }

            $suspendedWorkflows = array($suspendedWorkflow);
        } else {
            $suspendedWorkflows = $workflowService->getWorkflowsByStatus(Migration::STATUS_SUSPENDED);
        };

        $output->writeln('<info>Found ' . count($suspendedWorkflows) . ' suspended workflows</info>');

        if (!count($suspendedWorkflows)) {
            $output->writeln('Nothing to do');
            return 0;
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
                $output->writeln('<error>Workflow resuming cancelled!</error>');
                return 0;
            }
        }

        $executed = 0;
        $failed = 0;

        foreach($suspendedWorkflows as $suspendedWorkflow) {
            $output->writeln("<info>Resuming {$suspendedWorkflow->name}</info>");

            try {
                $workflowService->resumeWorkflow($suspendedWorkflow, !$input->getOption('no-transactions'));
                $executed++;
            } catch (\Exception $e) {
                $output->writeln("\n<error>Workflow failed! Reason: " . $e->getMessage() . "</error>\n");
                $failed++;
            }
        }

        $time = microtime(true) - $start;
        $output->writeln("Resumed $executed workflows, failed $failed");
        $output->writeln("Time taken: ".sprintf('%.2f', $time)." secs, memory: ".sprintf('%.2f', (memory_get_peak_usage(true) / 1000000)). ' MB');

        if ($failed) {
            return 2;
        }

        return 0;
    }
}
