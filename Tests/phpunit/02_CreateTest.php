<?php

include_once(__DIR__.'/CommandExecutingTest.php');

class CreateTest extends CommandExecutingTest
{
    public function testWorkflowCreation()
    {
        $output = $this->runCommand('kaliop:workflows:generate', array('bundle' => $this->targetBundle));
        $generatedFile = $this->saveGeneratedFile($output);
        $this->assertNotNull($generatedFile, "Failed creating a workflow");

        $output = $this->runCommand('kaliop:workflows:generate', array('bundle' => $this->targetBundle, '--format' => 'json'));
        $generatedFile = $this->saveGeneratedFile($output);
        $this->assertNotNull($generatedFile, "Failed creating a workflow in json format");

/// @todo test debug commands after creating the workflows
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
    }
}
