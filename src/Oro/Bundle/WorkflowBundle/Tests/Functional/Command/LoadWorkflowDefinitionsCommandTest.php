<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Symfony\Component\Console\Tester\CommandTester;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * @dbIsolation
 */
class LoadWorkflowDefinitionsCommandTest extends WebTestCase
{
    const NAME = 'oro:workflow:definitions:load';

    protected function setUp()
    {
        $this->initClient();

        $provider = $this->getContainer()->get('oro_workflow.configuration.provider.workflow_config');

        $reflectionClass = new \ReflectionClass(
            'Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider'
        );

        $reflectionProperty = $reflectionClass->getProperty('configDirectory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($provider, '/Tests/Functional/Command/DataFixtures/');
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $expectedMessages
     * @param array $expectedDefinitions
     * @param array $expectedProcesses
     */
    public function testExecute(array $expectedMessages, array $expectedDefinitions, array $expectedProcesses)
    {
        $repositoryWorkflow = $this->getRepository('OroWorkflowBundle:WorkflowDefinition');
        $repositoryProcess = $this->getRepository('OroWorkflowBundle:ProcessDefinition');

        $definitionsBefore = $repositoryWorkflow->findAll();
        $processesBefore = $repositoryProcess->findAll();

        $result = $this->runCommand(self::NAME);

        $this->assertNotEmpty($result);
        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }

        $definitions = $repositoryWorkflow->findAll();
        $processes = $repositoryProcess->findAll();

        $this->assertCount(count($definitionsBefore) + count($expectedDefinitions), $definitions);
        $this->assertCount(count($processesBefore) + count($expectedProcesses), $processes);
        foreach ($expectedDefinitions as $definitionName) {
            $this->assertDefinitionLoaded($definitions, $definitionName);
        }

        foreach ($expectedProcesses as $processName) {
            $this->assertProcessLoaded($processes, $processName);
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'expectedMessages' => [
                    'Loading workflow definitions...',
                ],
                'expectedDefinitions' => [
                    'first_workflow',
                    'second_workflow'
                ],
                'expectedProcesses' => [
                    'stpn__first_workflow__second_transition',
                ],
            ]
        ];
    }

    /**
     * @param array|WorkflowDefinition[] $definitions
     * @param string $name
     */
    protected function assertDefinitionLoaded(array $definitions, $name)
    {
        $found = false;

        foreach ($definitions as $definition) {
            if ($definition->getName() === $name) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * @param array|ProcessDefinition[] $processDefinitions
     * @param string $name
     */
    protected function assertProcessLoaded(array $processDefinitions, $name)
    {
        $found = false;

        foreach ($processDefinitions as $definition) {
            if (strpos($definition->getName(), $name) !== false) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * @param string $entityClass
     *
     * @return ObjectRepository
     */
    protected function getRepository($entityClass)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
    }
}
