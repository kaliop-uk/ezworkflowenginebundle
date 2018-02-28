<?php

namespace Kaliop\eZWorkflowEngineBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class GenerateCommand extends AbstractCommand
{
    const DIR_CREATE_PERMISSIONS = 0755;

    private $availableWorkflowFormats = array('yml', 'json');
    private $thisBundle = 'EzWorkflowEngineBundle';

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    #public function __construct(KernelInterface $kernel, $versionDirectory = 'Workflows')
    public function __construct(ConfigResolverInterface $configResolver)
    {
        #$this->versionDirectory = $versionDirectory;
        $this->configResolver = $configResolver;
    }

    /**
     * Configure the console command
     */
    protected function configure()
    {
        $this->setName('kaliop:workflows:generate')
            ->setDescription('Generate a blank workflow definition file.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The format of workflow file to generate (' . implode(', ', $this->availableWorkflowFormats) . ')', 'yml')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to generate the workflow definition file in. eg.: AcmeWorkflowsBundle')
            ->addArgument('name', InputArgument::OPTIONAL, 'The workflow name (will be prefixed with current date)', null)
            ->setHelp(<<<EOT
The <info>kaliop:workflows:generate</info> command generates a skeleton workflows definition file:

    <info>php ezpublish/console kaliop:workflows:generate bundlename</info>
EOT
            );
    }

    /**
     * Run the command and display the results.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int null or 0 if everything went fine, or an error code
     * @throws \InvalidArgumentException When an unsupported file type is selected
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $bundleName = $input->getArgument('bundle');
        $name = $input->getArgument('name');
        $fileType = $input->getOption('format');

        if ($bundleName == $this->thisBundle) {
            throw new \InvalidArgumentException("It is not allowed to create workflows in bundle '$bundleName'");
        }

        if (!in_array($fileType, $this->availableWorkflowFormats)) {
            throw new \InvalidArgumentException('Unsupported workflow file format ' . $fileType);
        }

        $workflowDirectory = $this->getWorkflowDirectory($bundleName);

        if (!is_dir($workflowDirectory)) {
            $output->writeln(sprintf(
                "Workflows directory <info>%s</info> does not exist. I will create it now....",
                $workflowDirectory
            ));

            if (mkdir($workflowDirectory, self::DIR_CREATE_PERMISSIONS, true)) {
                $output->writeln(sprintf(
                    "Workflows directory <info>%s</info> has been created",
                    $workflowDirectory
                ));
            } else {
                throw new FileException(sprintf(
                    "Failed to create workflows directory %s.",
                    $workflowDirectory
                ));
            }
        }

        $date = date('YmdHis');

        if ($name == '') {
            $name = 'placeholder';
        }
        $fileName = $date . '_' . $name . '.' . $fileType;

        $path = $workflowDirectory . '/' . $fileName;

        $warning = $this->generateWorkflowFile($path, $fileType);

        $output->writeln(sprintf("Generated new workflow file: <info>%s</info>", $path));

        if ($warning != '') {
            $output->writeln("<comment>$warning</comment>");
        }
    }

    /**
     * Generates a workflow definition file.
     *
     * @param string $path filename to file to generate (full path)
     * @param string $fileType The type of workflow file to generate
     * @param array $parameters
     * @return string A warning message in case file generation was OK but there was something weird
     * @throws \Exception
     */
    protected function generateWorkflowFile($path, $fileType, $parameters = array())
    {
        $warning = '';

        // Generate workflow file by template
        $template = 'Workflow.' . $fileType . '.twig';
        $templatePath = $this->getApplication()->getKernel()->getBundle($this->thisBundle)->getPath() . '/Resources/views/WorkflowTemplate/';
        if (!is_file($templatePath . $template)) {
            throw new \Exception("Generation of a workflow example is not supported with format '$fileType'");
        }

        $code = $this->getContainer()->get('twig')->render($this->thisBundle . ':WorkflowTemplate:' . $template, $parameters);

        file_put_contents($path, $code);

        return $warning;
    }

    /**
     * @param string $bundleName a bundle name or filesystem path to a directory
     * @return string
     */
    protected function getWorkflowDirectory($bundleName)
    {
        // Allow direct usage of a directory path instead of a bundle name
        if (strpos($bundleName, '/') !== false && is_dir($bundleName)) {
            return rtrim($bundleName, '/');
        }

        $activeBundles = array();
        foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
            $activeBundles[] = $bundle->getName();
        }
        asort($activeBundles);
        if (!in_array($bundleName, $activeBundles)) {
            throw new \InvalidArgumentException("Bundle '$bundleName' does not exist or it is not enabled. Try with one of:\n" . implode(', ', $activeBundles));
        }

        $bundle = $this->getApplication()->getKernel()->getBundle($bundleName);
        #$workflowDirectory = $bundle->getPath() . '/' . $this->getContainer()->getParameter('ez_workflowengine_bundle.workflow_directory');
        $workflowDirectory = $bundle->getPath() . '/' .  $this->configResolver->getParameter('workflow_directory','ez_workflowengine_bundle');

        return $workflowDirectory;
    }
}
