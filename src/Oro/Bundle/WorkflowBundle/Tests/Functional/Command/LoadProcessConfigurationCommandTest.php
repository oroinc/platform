<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Command\LoadProcessConfigurationCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Component\Testing\ReflectionUtil;

class LoadProcessConfigurationCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $provider = $this->getContainer()->get('oro_workflow.configuration.provider.process_config');
        ReflectionUtil::setPropertyValue($provider, 'configDirectory', '/Tests/Functional/Command/DataFixtures/');
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $expectedMessages, array $expectedDefinitions, array $expectedTriggers)
    {
        $definitionsBefore = $this->getRepository(ProcessDefinition::class)->findAll();
        $triggersBefore = $this->getRepository(ProcessTrigger::class)->findAll();

        $result = $this->runCommand(LoadProcessConfigurationCommand::getDefaultName());

        $this->assertNotEmpty($result);
        foreach ($expectedMessages as $message) {
            self::assertStringContainsString($message, $result);
        }

        $definitions = $this->getRepository(ProcessDefinition::class)->findAll();

        $this->assertCount(count($definitionsBefore) + 2, $definitions);
        foreach ($expectedDefinitions as $definition) {
            $this->assertDefinitionLoaded($definitions, $definition);
        }

        $triggers = $this->getRepository(ProcessTrigger::class)->findAll();

        $this->assertCount(count($triggersBefore) + 4, $triggers);
        foreach ($expectedTriggers as $trigger) {
            $this->assertTriggerLoaded($triggers, $trigger['name'], $trigger['event'], $trigger['cron']);
        }
    }

    public function executeDataProvider(): array
    {
        return [
            [
                'expectedMessages' => [
                    '"test_definition" - created',
                    '"another_definition" - created',
                    'Process definitions configuration updates are stored into database',
                    'test_definition [update] - created',
                    'process trigger: test_definition [create] - created',
                    'process trigger: test_definition [delete] - created',
                    'process trigger: test_definition [cron:*/1 * * * *] - created',
                    'process triggers modifications stored in DB'
                ],
                'expectedDefinitions' => [
                    'test_definition',
                    'another_definition'
                ],
                'expectedTriggers' => [
                    ['name' => 'test_definition', 'event' => 'update', 'cron' => null],
                    ['name' => 'test_definition', 'event' => 'create', 'cron' => null],
                    ['name' => 'test_definition', 'event' => 'delete', 'cron' => null],
                    ['name' => 'test_definition', 'event' => null, 'cron' => '*/1 * * * *']
                ]
            ]
        ];
    }

    private function getRepository(string $className): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository($className);
    }

    private function assertDefinitionLoaded(array $definitions, string $name): void
    {
        $found = false;
        /** @var ProcessDefinition $definition */
        foreach ($definitions as $definition) {
            if ($definition->getName() === $name) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    private function assertTriggerLoaded(array $triggers, string $name, ?string $event, ?string $cron): void
    {
        $found = false;
        /** @var ProcessTrigger $trigger */
        foreach ($triggers as $trigger) {
            if ($trigger->getEvent() === $event &&
                $trigger->getCron() === $cron &&
                $trigger->getDefinition()->getName() === $name
            ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }
}
