<?php

include_once(__DIR__.'/CommandExecutingTest.php');

class CliTest extends CommandExecutingTest
{
    /**
     * @param string $command
     * @param array $parameters
     * @return void
     *
     * @dataProvider provideGenericCommandsList
     */
    public function testGenericCommands($command, $parameters)
    {
        $output = $this->runCommand($command, $parameters);
    }

    public function provideGenericCommandsList()
    {
        $out = array(
            array('kaliop:workflows:debug', array()),
            //array('kaliop:workflows:debug', array('--path' => ...)),
            array('kaliop:workflows:status', array()),
            array('kaliop:workflows:status', array('--summary' => true)),
            array('kaliop:workflows:resume', array()),
            array('kaliop:workflows:resume', array('--no-transactions' => true)),
            array('kaliop:workflows:cleanup', array()),
            array('kaliop:workflows:cleanup', array('--failed' => true)),
            array('kaliop:workflows:cleanup', array('--dry-run' => true)),
        );

        return $out;
    }
}
