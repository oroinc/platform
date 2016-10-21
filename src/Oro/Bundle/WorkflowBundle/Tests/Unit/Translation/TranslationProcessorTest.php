<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TranslationProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationProcessor */
    private $processor;

    /** @var WorkflowTranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $translationHelper;

    protected function setUp()
    {

        $this->translationHelper = $this->getMockBuilder(WorkflowTranslationHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->processor = new TranslationProcessor($this->translationHelper);
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
        $config = ['label' => 24];

        $result = $this->processor->prepare('test_workflow', $config);

        $this->assertEquals(
            $result,
            ['label' => 'oro.workflow.test_workflow.label'],
            'should return modified with key configuration back'
        );
    }

    public function testImplementsHandler()
    {
        $this->assertInstanceOf(ConfigurationHandlerInterface::class, $this->processor);
    }

    public function testHandle()
    {
        $configuration = ['name' => 'test_workflow', 'label' => 'wflabel'];

        $this->translationHelper->expects($this->at(0))
            ->method('saveTranslation')
            ->with('oro.workflow.test_workflow.label', 'wflabel');

        $this->processor->handle($configuration);
    }

    public function tesHandleIncorrectConfigFormatException()
    {
        $config = [];
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Workflow configuration for handler must contain valid `name` node.'
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
        $definition = (new WorkflowDefinition())->setName('test_workflow');
        $changes = new WorkflowChangesEvent($definition);

        $this->translationHelper->expects($this->at(0))
            ->method('ensureTranslationKey')
            ->with('oro.workflow.test_workflow.label');

        $this->processor->ensureTranslationKeys($changes);
    }

    public function testClearTranslationKeys()
    {
        $updatedDefinition = (new WorkflowDefinition())
            ->setName('test_workflow')
            ->setLabel('test_workflow_label_translation_key')
            ->setConfiguration(
                ['transitions' => ['transition_1' => ['label' => 'test_workflow_transition_1_translation_key']]]
            );
        $previousDefinition = (new WorkflowDefinition())
            ->setName('test_workflow')
            ->setLabel('test_workflow_label_translation_key')
            ->setConfiguration(
                ['transitions' => ['transition_2' => ['label' => 'test_workflow_transition_2_translation_key']]]
            );

        $changes = new WorkflowChangesEvent($updatedDefinition, $previousDefinition);

        $this->translationHelper->expects($this->at(0))
            ->method('ensureTranslationKey')
            ->with('test_workflow_label_translation_key');
        $this->translationHelper->expects($this->at(1))
            ->method('ensureTranslationKey')
            ->with('test_workflow_transition_1_translation_key');

        $this->translationHelper->expects($this->at(2))
            ->method('removeTranslationKey')
            ->with('test_workflow_transition_2_translation_key');


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
        $deletedDefinition = (new WorkflowDefinition())->setName('test_workflow')->setLabel('label_translation_key');

        $changes = new WorkflowChangesEvent($deletedDefinition);

        $this->translationHelper->expects($this->at(0))
            ->method('removeTranslationKey')->with('label_translation_key');

        $this->processor->deleteTranslationKeys($changes);
    }
}
