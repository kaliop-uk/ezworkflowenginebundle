<?php

namespace Kaliop\eZWorkflowEngineBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Table;
use Kaliop\eZMigrationBundle\API\Value\Migration;

/**
 * Command to display the defined workflows.
 *
 * @todo add a 'summary' option
 */
class StatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('kaliop:workflows:status')
            ->addOption('summary', null, InputOption::VALUE_NONE, "Only print summary information")
            ->setDescription('List the currently executing or already executed workflows')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workflowService = $this->getWorkflowService();

        $workflows = $workflowService->getWorkflows();

        if (!count($workflows)) {
            $output->writeln('<info>No workflows found</info>');
            return;
        }

        $summary = array(
            Migration::STATUS_TODO => array('To do', 0),
            Migration::STATUS_STARTED => array('Started', 0),
            Migration::STATUS_DONE => array('Done', 0),
            Migration::STATUS_SUSPENDED => array('Suspended', 0),
            Migration::STATUS_FAILED => array('Failed', 0),
            Migration::STATUS_SKIPPED => array('Skipped', 0),
            Migration::STATUS_PARTIALLY_DONE => array('Partially done', 0),
        );

        $i = 1;
        foreach ($workflows as $workflow) {

            if (!isset($summary[$workflow->status])) {
                $summary[$workflow->status] = array($workflow->status, 0);
            }
            $summary[$workflow->status][1]++;
            if ($input->getOption('summary')) {
                continue;
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

            switch ($workflow->status) {
                case Migration::STATUS_FAILED:
                    $name = '<error>' . $workflow->name . '</error>';
                    break;
                default:
                    $name = $workflow->name;
            }

            $data[] = array(
                $i++,
                $name,
                $workflow->signalName,
                $status,
                $workflow->executionDate != null ? date("Y-m-d H:i:s", $workflow->executionDate) : '',
                $workflow->executionError,
            );

        }

        if ($input->getOption('summary')) {
            $output->writeln("\n <info>==</info> Workflows Summary\n");
            // do not print info about the not yet supported case
            unset($summary[Migration::STATUS_PARTIALLY_DONE]);
            $data = $summary;
            $headers = array('Status', 'Count');
        } else {
            $headers = array('#', 'Workflow', 'Signal', 'Status', 'Executed on', 'Notes');
        }

        $table = new Table($output);
        $table
            ->setHeaders($headers)
            ->setRows($data);
        $table->render();
    }
}
