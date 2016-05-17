<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Symfony\Component\Console\Tester\CommandTester;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

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
     */
    public function testExecute(array $expectedMessages, array $expectedDefinitions)
    {
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroWorkflowBundle:WorkflowDefinition')
            ->getRepository('OroWorkflowBundle:WorkflowDefinition');

        $definitionsBefore = $repository->findAll();

        $result = $this->runCommand(self::NAME);

        $this->assertNotEmpty($result);
        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }

        $definitions = $repository->findAll();

        $this->assertCount(count($definitionsBefore) + 2, $definitions);
        foreach ($expectedDefinitions as $definition) {
            $this->assertDefinitionLoaded($definitions, $definition);
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
            ]
        ];
    }

    /**
     * @param array|ProcessDefinition[] $definitions
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
}
