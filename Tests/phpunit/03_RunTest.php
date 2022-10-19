<?php

include_once(__DIR__.'/CommandExecutingTest.php');

class RunTest extends CommandExecutingTest
{
    protected $prefix = 'WorkflowTest03';

    protected function doSetUp()
    {
        parent::doSetUp();

        if (is_file($this->dslDir.'/good/'.$this->prefix.'_pre.yml')) {
            $this->runMigration($this->dslDir.'/good/'.$this->prefix.'_pre.yml');
        }
    }

    protected function doTearDown()
    {
        if (is_file($this->dslDir.'/good/'.$this->prefix.'_post.yml')) {
            $this->runMigration($this->dslDir.'/good/'.$this->prefix.'_post.yml');
        }

        parent::doTearDown();
    }

    /**
     * @dataProvider provideFolderList
     */
    public function testWorkflowExecution($dir)
    {
        $parts = explode('_', basename($dir));
        $num = $parts[0];
        if (!is_file($dir.'/'.$this->prefix.'_'.$num.'_workflow.yml') ||
            !is_file($dir.'/'.$this->prefix.'_'.$num.'_trigger.yml')) {
            throw new \Exception("Test in dir '$dir' misses trigger or workflow definitions");
        }
        $bundle = static::$kernel->getBundle($this->targetBundle);
        $targetDir = $bundle->getPath() . "/" . $this->getContainer()->getParameter('ez_workflowengine_bundle.workflow_directory');

        // install workflow
        copy($dir.'/'.$this->prefix.'_'.$num.'_workflow.yml', $targetDir.'/'.$this->prefix.'_'.$num.'_workflow.yml');
        $this->leftovers[] = $targetDir.'/'.$this->prefix.'_'.$num.'_workflow.yml';
        $out = $this->runCommand('kaliop:workflows:debug');
        $this->assertRegExp('?\| ' . $this->prefix.'_'.$num.'_workflow.yml' . ' +\|?', $out);
        // run trigger
        $this->runMigration($dir.'/'.$this->prefix.'_'.$num.'_trigger.yml');
        // wait ?
        // run verification
        if (is_file($dir.'/'.$this->prefix.'_'.$num.'_verify.yml')) {
            $out = $this->runMigration($dir.'/'.$this->prefix.'_'.$num.'_verify.yml');
        }
        // check that the workflow was executed
        $out = $this->runCommand('kaliop:workflows:status');
        $this->assertRegExp('?\| .+' . $this->prefix.'_'.$num.'_workflow.yml' . ' +\|.+\| executed +\|?', $out);
    }

    public function provideFolderList()
    {
        $out = array();
        foreach(scandir($this->dslDir . '/good') as $name) {
            $filePath = $this->dslDir . '/good/' . $name;
            if (is_dir($filePath) && $name !== '.' && $name !== '..') {
                $out[] = array($filePath);
            }
        }
        return $out;
    }

    protected function runMigration($path, array $params = array())
    {
        /// @todo should we first remove the migration if it was already run?
        $params = array_merge($params, array('--path' => array($path), '-n' => true, '-f' => true));
        $out = $this->runCommand('kaliop:migration:migrate', $params);
        $this->assertNotContains('Skipping ' . basename($path), $out, "Migration definition is incorrect?");
        return $out;
    }
}
