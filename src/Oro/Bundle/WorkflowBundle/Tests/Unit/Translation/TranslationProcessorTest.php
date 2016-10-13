<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Translation\TranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowTranslationFieldsIterator;

class TranslationProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationProcessor */
    private $processor;

    /** @var WorkflowTranslationFieldsIterator|\PHPUnit_Framework_MockObject_MockObject */
    private $fieldsIterator;

    /** @var TranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $translationHelper;

    protected function setUp()
    {
        $this->fieldsIterator = $this->getMockBuilder(WorkflowTranslationFieldsIterator::class)
            ->disableOriginalConstructor()->getMock();

        $this->translationHelper = $this->getMockBuilder(TranslationHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->processor = new TranslationProcessor($this->fieldsIterator, $this->translationHelper);
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

        $this->fieldsIterator->expects($this->once())->method('iterateConfigTranslationFields')
            ->with('test_workflow', $config)
            ->willReturn($iterationChanges);

        $result = $this->processor->prepare('test_workflow', $config);

        $this->assertEquals(
            (object)[
                'key1' => 'key1',
                'key2' => 'key2'
            ],
            $iterationChanges,
            'Iterated keys must be placed to values trough reference.'
        );

        $this->assertEquals($result, $config, 'should return configuration back');
    }

    public function testHandle()
    {
        $configuration = ['name' => 'test_workflow'];

        $iteratedFields = [
            'key1' => 'value1',
            'key2' => null,
            'key3' => 'value3',
            'key4' => '',
        ];

        $this->fieldsIterator->expects($this->once())
            ->method('iterateConfigTranslationFields')
            ->with('test_workflow', $configuration)->willReturn($iteratedFields);

        $this->translationHelper->expects($this->at(0))
            ->method('saveTranslation')
            ->with('key1', 'value1');
        $this->translationHelper->expects($this->at(1))
            ->method('saveTranslation')
            ->with('key3', 'value3');

        $this->processor->handle($configuration);
    }

    public function tesHandleIncorrectConfigFormatException()
    {
        $config = [];
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Workflow configuration for handler must contain `name` node.'
        );

        $this->processor->handle($config);
    }
}
