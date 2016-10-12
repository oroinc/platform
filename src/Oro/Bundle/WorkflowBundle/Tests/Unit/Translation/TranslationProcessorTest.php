<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeySourceInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\TranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;

class TranslationProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var TranslationKeyGenerator|\PHPUnit_Framework_MockObject_MockObject */
    private $generator;

    /** @var TranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $helper;

    /** @var TranslationProcessor */
    private $processor;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder(TranslationManager::class)->disableOriginalConstructor()->getMock();
        $this->generator = $this->getMock(TranslationKeyGenerator::class);
        $this->helper = $this->getMockBuilder(TranslationHelper::class)->disableOriginalConstructor()->getMock();
        $this->processor = new TranslationProcessor($this->helper, $this->manager, $this->generator);
    }

    /**
     * @dataProvider processDataProvider
     *
     * @param WorkflowDefinition $expectedDefinition
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $previousDefinition
     */
    public function testProcess(
        WorkflowDefinition $expectedDefinition = null,
        WorkflowDefinition $definition = null,
        WorkflowDefinition $previousDefinition = null
    ) {
        if ($definition) {
            $this->helper->expects($this->exactly(4))->method('updateNodeKeys');
            $this->helper->expects($this->exactly(4))->method('cleanupNodeKeys');
        }

        if ((!$definition && $previousDefinition) ||
            ($definition && $previousDefinition && $definition->getName() !== $previousDefinition->getName())
        ) {
            $this->manager->expects($this->once())->method('removeTranslationKeysByPrefix');
        } else {
            $this->manager->expects($this->never())->method('removeTranslationKeysByPrefix');
        }

        $this->generator->expects($this->any())->method('generate')->willReturnCallback(
            function (TranslationKeySourceInterface $keySource) {
                $data = $keySource->getData();

                return sprintf('key.%s.key', end($data));
            }
        );

        $this->processor->process($definition, $previousDefinition);
        $this->assertEquals($expectedDefinition, $definition);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        $config = [
            WorkflowConfiguration::NODE_STEPS => [],
            WorkflowConfiguration::NODE_TRANSITIONS => [],
            WorkflowConfiguration::NODE_ATTRIBUTES => [],
        ];
        $expectedEmpty = (new WorkflowDefinition())
            ->setName('test_wfl_name')
            ->setConfiguration($config)
            ->setLabel('key.test_wfl_name.key');
        $definitionEmpty = (new WorkflowDefinition())->setName('test_wfl_name')->setConfiguration($config);
        $prevDefinition = new WorkflowDefinition();
        $prevDefinition->setName('test_name');

        $config = [
            WorkflowConfiguration::NODE_STEPS => ['step1' => []],
            WorkflowConfiguration::NODE_TRANSITIONS => ['transaction1' => []],
            WorkflowConfiguration::NODE_ATTRIBUTES => ['attribute1' => []],
        ];
        $definition = clone $definitionEmpty;
        $definition->setConfiguration($config);
        $expected = clone $definition;
        $expected->setName($definition->getName())->setLabel(sprintf('key.%s.key', $expected->getName()));

        return [
            'empty config' => [
                $expectedEmpty,
                $definitionEmpty,
                $prevDefinition
            ],
            'full config' => [
                $expected,
                $definition,
                $prevDefinition
            ],
            'on delete' => [
                null,
                null,
                $prevDefinition
            ],
            'on create' => [
                $expectedEmpty,
                $definitionEmpty,
                null
            ],
            'different names' => [
                $expectedEmpty,
                $definitionEmpty,
                (new WorkflowDefinition())->setName('other_name')
            ],
        ];
    }
}
