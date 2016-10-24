<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowTranslationKeysSubscriber;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowTranslationKeysSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowTranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $workflowTranslationHelper;

    /** @var WorkflowTranslationKeysSubscriber */
    private $translationKeysSubscriber;

    protected function setUp()
    {
        $this->workflowTranslationHelper = $this->getMockBuilder(WorkflowTranslationHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->translationKeysSubscriber = new WorkflowTranslationKeysSubscriber($this->workflowTranslationHelper);
    }

    public function testImplementsSubscriberInterface()
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, $this->translationKeysSubscriber);
    }

    public function testEnsureTranslationKeys()
    {
        $definition = (new WorkflowDefinition())->setName('test_workflow');
        $changes = new WorkflowChangesEvent($definition);

        $this->workflowTranslationHelper->expects($this->at(0))
            ->method('ensureTranslationKey')
            ->with('oro.workflow.test_workflow.label');

        $this->translationKeysSubscriber->ensureTranslationKeys($changes);
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

        $this->workflowTranslationHelper->expects($this->at(0))
            ->method('ensureTranslationKey')
            ->with('test_workflow_label_translation_key');
        $this->workflowTranslationHelper->expects($this->at(1))
            ->method('ensureTranslationKey')
            ->with('test_workflow_transition_1_translation_key');

        $this->workflowTranslationHelper->expects($this->at(2))
            ->method('removeTranslationKey')
            ->with('test_workflow_transition_2_translation_key');

        $this->translationKeysSubscriber->clearTranslationKeys($changes);
    }

    public function testClearLogicExceptionOnAbsentPreviousDefinition()
    {
        $updatedDefinition = new WorkflowDefinition();
        $previousDefinition = null;
        $changes = new WorkflowChangesEvent($updatedDefinition, $previousDefinition);

        $this->setExpectedException(\LogicException::class, 'Previous WorkflowDefinition expected. But got null.');

        $this->translationKeysSubscriber->clearTranslationKeys($changes);
    }

    public function testDeleteTranslationKeys()
    {
        $deletedDefinition = (new WorkflowDefinition())->setName('test_workflow')->setLabel('label_translation_key');

        $changes = new WorkflowChangesEvent($deletedDefinition);

        $this->workflowTranslationHelper->expects($this->at(0))
            ->method('removeTranslationKey')->with('label_translation_key');

        $this->translationKeysSubscriber->deleteTranslationKeys($changes);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                WorkflowEvents::WORKFLOW_AFTER_CREATE => 'ensureTranslationKeys',
                WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'clearTranslationKeys',
                WorkflowEvents::WORKFLOW_AFTER_DELETE => 'deleteTranslationKeys'
            ],
            WorkflowTranslationKeysSubscriber::getSubscribedEvents()
        );
    }
}
