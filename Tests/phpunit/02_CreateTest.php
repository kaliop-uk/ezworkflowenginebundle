<?php

include_once(__DIR__.'/CommandExecutingTest.php');

class CliTest extends CommandExecutingTest
{
    public function testWorkflowCreation()
    {
        $output = $this->runCommand('kaliop:workflows:generate', array('bundle' => self::$targetBundle));
        $generatedFile = $this->saveGeneratedFile($output);
        $this->assertNotNull($generatedFile, "Failed creating a workflow");

        $output = $this->runCommand('kaliop:workflows:generate', array('bundle' => self::$targetBundle, '--format' => 'json'));
        $generatedFile = $this->saveGeneratedFile($output);
        $this->assertNotNull($generatedFile, "Failed creating a workflow in json format");

        /// @todo test info commands after creating the workflows
    }

    protected function saveGeneratedFile($output)
    {
        if (preg_match('/Generated new workflow file: +(.*)/', $output, $matches)) {
            $this->leftovers[] = $matches[1];
            return $matches[1];
        }

        return null;
    }

    protected function checkGeneratedFile($filePath)
    {
        // Check that the generated file can be parsed as valid Workflow Definition
        $output = $this->runCommand('kaliop:workflows:debug');
        $this->assertRegExp('?\| ' . basename($filePath) . ' +\|?', $output);

        // We should really test generated migrations by executing them, but for the moment we have a few problems:
        // 1. we should patch them after generation, eg. replacing 'folder' w. something else (to be able to create and delete the content-type)
        // 2. generated migration for 'anon' user has a limitation with borked siteaccess
        // 3. generated migration for 'folder' contenttype has a borked field-settings definition
        if (false) {
            $output = $this->runCommand('kaliop:migration:migrate', array('--path' => array($filePath), '-n' => null));
            $this->assertRegexp('?\| ' . basename($filePath) . ' +\| +\|?', $output);
        }
    }
}
