<?php

namespace Kaliop\eZWorkflowEngineBundle\Core\DefinitionParser;

use Kaliop\eZMigrationBundle\Core\DefinitionParser\AbstractDefinitionParser as BaseParser;
use Kaliop\eZMigrationBundle\API\Value\MigrationDefinition;
use Kaliop\eZWorkflowEngineBundle\Api\Value\WorkflowDefinition;
use Kaliop\eZMigrationBundle\API\Collection\MigrationStepsCollection;

class AbstractDefinitionParser extends BaseParser
{
    protected function parseMigrationDefinitionData($data, MigrationDefinition $definition, $format = 'Yaml')
    {
        $migrationDefinition = parent::parseMigrationDefinitionData($data, $definition, $format);

        $status = $migrationDefinition->status;
        $steps = $migrationDefinition->steps->getArrayCopy();
        $error = $migrationDefinition->parsingError;

        $signalName = '';
        // false stands for 'use current user'
        $user = false;
        $useTransaction = false;
        $avoidRecursion = false;

        if ($status == MigrationDefinition::STATUS_PARSED) {

            do { // goto is evil ;-)
                if (count($steps) < 1) {
                    $status = MigrationDefinition::STATUS_INVALID;
                    $error = "$format file '{$definition->path}' is not a valid workflow definition as it has no workflow manifest";
                    break;
                }

                /** @var \Kaliop\eZMigrationBundle\API\Value\MigrationStep $manifestStep */
                $manifestStep = $steps[0];
                if ($manifestStep->type !== WorkflowDefinition::MANIFEST_STEP_TYPE) {
                    $status = MigrationDefinition::STATUS_INVALID;
                    $error = "$format file '{$definition->path}' is not a valid workflow definition as its 1st step is not the workflow manifest";
                    break;
                }

                array_shift($steps);

                $dsl = $manifestStep->dsl;
                if (!isset($dsl[WorkflowDefinition::MANIFEST_SIGNAL_ELEMENT])) {
                    $status = MigrationDefinition::STATUS_INVALID;
                    $error = "$format file '{$definition->path}' is not a valid workflow definition as its 1st step does not define a signal";
                    break;
                }
                $signalName = $dsl[WorkflowDefinition::MANIFEST_SIGNAL_ELEMENT];

                if (isset($dsl[WorkflowDefinition::MANIFEST_RUNAS_ELEMENT])) {
                    $user = $dsl[WorkflowDefinition::MANIFEST_RUNAS_ELEMENT];
                }

                if (isset($dsl[WorkflowDefinition::MANIFEST_USETRANSACTION_ELEMENT])) {
                    $useTransaction = $dsl[WorkflowDefinition::MANIFEST_USETRANSACTION_ELEMENT];
                }

                if (isset($dsl[WorkflowDefinition::MANIFEST_AVOIDRECURSION_ELEMENT])) {
                    $avoidRecursion = $dsl[WorkflowDefinition::MANIFEST_AVOIDRECURSION_ELEMENT];
                }

            } while(false);
        }

        return new WorkflowDefinition(
            $migrationDefinition->name,
            $migrationDefinition->path,
            $migrationDefinition->rawDefinition,
            $status,
            $steps,
            $error,
            $signalName,
            $user,
            $useTransaction,
            $avoidRecursion
        );
    }
}
