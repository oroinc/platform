<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Command\LoadWorkflowDefinitionsCommand;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;

abstract class WorkflowTestCase extends WebTestCase
{
    /**
     * @var \ReflectionProperty
     */
    private static $configDirPropertyReflection;

    /**
     * Loads workflow by command from workflow.yml file under specified directory.
     * @param string $directory
     * @return string
     */
    public static function loadWorkflowFrom($directory)
    {
        self::setConfigLoadDirectory($directory);
        return self::runCommand(LoadWorkflowDefinitionsCommand::NAME, ['--no-ansi']);
    }

    /**
     * @param string $directory
     */
    private static function setConfigLoadDirectory($directory)
    {
        self::getConfigDirPropertyReflection()->setValue(
            self::getContainer()->get('oro_workflow.configuration.provider.workflow_config'),
            $directory
        );
    }

    /**
     * @return \ReflectionProperty
     */
    private static function getConfigDirPropertyReflection()
    {
        $reflectionClass = new \ReflectionClass(WorkflowConfigurationProvider::class);

        if (!self::$configDirPropertyReflection) {
            self::$configDirPropertyReflection = $reflectionClass->getProperty('configDirectory');
            self::$configDirPropertyReflection->setAccessible(true);
        }

        return self::$configDirPropertyReflection;
    }
}
