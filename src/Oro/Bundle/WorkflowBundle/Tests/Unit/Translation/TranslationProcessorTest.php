<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Translation\TranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowTranslationFieldsIterator;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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

    protected function tearDown()
    {
        unset($this->fieldsIterator, $this->translationHelper, $this->processor);
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

    public function testImplementsHandler()
    {
        $this->assertInstanceOf(ConfigurationHandlerInterface::class, $this->processor);
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

    public function testImplementsSubscriberInterface()
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, $this->processor);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                WorkflowEvents::WORKFLOW_AFTER_CREATE => 'ensureTranslationKeys',
                WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'clearTranslationKeys',
                WorkflowEvents::WORKFLOW_AFTER_DELETE => 'deleteTranslationKeys'
            ],
            TranslationProcessor::getSubscribedEvents()
        );
    }

    public function testEnsureTranslationKeys()
    {
        $definition = new WorkflowDefinition();
        $changes = new WorkflowChangesEvent($definition);

        $this->fieldsIterator->expects($this->once())
            ->method('iterateWorkflowDefinition')
            ->with($definition)
            ->willReturn(['key1', 'key2']);

        $this->translationHelper->expects($this->at(0))->method('ensureTranslationKey')->with('key1');
        $this->translationHelper->expects($this->at(1))->method('ensureTranslationKey')->with('key2');

        $this->processor->ensureTranslationKeys($changes);
    }

    public function testClearTranslationKeys()
    {
        $updatedDefinition = new WorkflowDefinition();
        $previousDefinition = new WorkflowDefinition();
        $changes = new WorkflowChangesEvent($updatedDefinition, $previousDefinition);

        $this->fieldsIterator->expects($this->at(0))
            ->method('iterateWorkflowDefinition')
            ->with($updatedDefinition)
            ->willReturn(['key3', 'key4']);

        $this->fieldsIterator->expects($this->at(1))
            ->method('iterateWorkflowDefinition')
            ->with($previousDefinition)
            ->willReturn(new \ArrayIterator(['key1', 'key2', 'key3']));

        $this->translationHelper->expects($this->at(0))->method('ensureTranslationKey')->with('key3');
        $this->translationHelper->expects($this->at(1))->method('ensureTranslationKey')->with('key4');

        $this->translationHelper->expects($this->at(2))->method('removeTranslationKey')->with('key1');
        $this->translationHelper->expects($this->at(3))->method('removeTranslationKey')->with('key2');

        $this->processor->clearTranslationKeys($changes);
    }

    public function testClearLogicExceptionOnAbsentPreviousDefinition()
    {
        $updatedDefinition = new WorkflowDefinition();
        $previousDefinition = null;
        $changes = new WorkflowChangesEvent($updatedDefinition, $previousDefinition);

        $this->setExpectedException(\LogicException::class, 'Previous WorkflowDefinition expected. But got null.');

        $this->processor->clearTranslationKeys($changes);
    }

    public function testDeleteTranslationKeys()
    {
        $deletedDefinition = new WorkflowDefinition();

        $changes = new WorkflowChangesEvent($deletedDefinition);

        $this->fieldsIterator->expects($this->once())
            ->method('iterateWorkflowDefinition')
            ->with($deletedDefinition)
            ->willReturn(['key1_to_delete', 'key2_to_delete']);

        $this->translationHelper->expects($this->at(0))->method('removeTranslationKey')->with('key1_to_delete');
        $this->translationHelper->expects($this->at(1))->method('removeTranslationKey')->with('key2_to_delete');

        $this->processor->deleteTranslationKeys($changes);
    }
}
