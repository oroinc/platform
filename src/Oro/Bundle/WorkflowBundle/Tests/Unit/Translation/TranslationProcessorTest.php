<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowTranslationFieldsIterator;

class TranslationProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslationProcessor
     */
    private $processor;

    /**
     * @var WorkflowTranslationFieldsIterator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldsIterator;

    protected function setUp()
    {
        $this->fieldsIterator = $this->getMockBuilder(WorkflowTranslationFieldsIterator::class)
            ->disableOriginalConstructor()->getMock();

        $this->processor = new TranslationProcessor($this->fieldsIterator);
    }

    public function testImplementsBuilderExtension()
    {
        $this->assertInstanceOf(WorkflowDefinitionBuilderExtensionInterface::class, $this->processor);
    }

    public function testPrepare()
    {
        $config = ['42' => 24];

        //test iterator modifications
        $iterationChanges = (object)['key1' => 'val', 'key2' => null];

        $this->fieldsIterator->expects($this->once())->method('iterateConfigFields')
            ->with('test_workflow', $config)
            ->willReturn($iterationChanges);

        $result = $this->processor->prepare('test_workflow', $config);

        $this->assertEquals(
            (object)[
                'key1' => 'key1',
                'key2' => 'key2'
            ],
            $iterationChanges,
            'Modifications must be provided on iterated values.'
        );

        $this->assertEquals($result, $config, 'should return configuration back');
    }
}
