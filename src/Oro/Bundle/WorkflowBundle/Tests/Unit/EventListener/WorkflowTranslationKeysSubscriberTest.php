<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowTranslationKeysSubscriber;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowTranslationKeysSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $translationManager;

    /** @var WorkflowTranslationKeysSubscriber */
    private $translationKeysSubscriber;

    protected function setUp()
    {
        $this->translationManager = $this->getMockBuilder(TranslationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationKeysSubscriber = new WorkflowTranslationKeysSubscriber($this->translationManager);
    }

    public function testImplementsSubscriberInterface()
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, $this->translationKeysSubscriber);
    }

    public function testEnsureTranslationKeys()
    {
        $definition = (new WorkflowDefinition())->setName('test_workflow');
        $changes = new WorkflowChangesEvent($definition);

        $this->translationManager->expects($this->once())
            ->method('findTranslationKey')
            ->with('oro.workflow.test_workflow.label', WorkflowTranslationHelper::TRANSLATION_DOMAIN);
        $this->translationManager->expects($this->once())->method('flush');

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
                [
                    'transitions' => [
                        'transition_2' => [
                            'label' => 'test_workflow_transition_2_translation_key',
                            'button_label' => 'test_workflow_transition_2_translation_button_label_key',
                            'button_title' => 'test_workflow_transition_2_translation_button_title_key',
                            'message' => 'test_workflow_transition_2_translation_message_key'
                        ]
                    ]
                ]
            );

        $changes = new WorkflowChangesEvent($updatedDefinition, $previousDefinition);

        $findTranslationKeys = [];
        $removeTranslationKeys = [];

        $this->translationManager->expects($this->any())
            ->method('findTranslationKey')
            ->willReturnCallback(
                function ($key, $domain) use (&$findTranslationKeys) {
                    $this->assertEquals(WorkflowTranslationHelper::TRANSLATION_DOMAIN, $domain);

                    $findTranslationKeys[] = $key;
                }
            );

        $this->translationManager->expects($this->any())
            ->method('removeTranslationKey')
            ->willReturnCallback(
                function ($key, $domain) use (&$removeTranslationKeys) {
                    $this->assertEquals(WorkflowTranslationHelper::TRANSLATION_DOMAIN, $domain);

                    $removeTranslationKeys[] = $key;
                }
            );

        $this->translationManager->expects($this->once())->method('flush');

        $this->translationKeysSubscriber->clearTranslationKeys($changes);

        $this->assertEquals(
            [
                'test_workflow_label_translation_key',
                'test_workflow_transition_1_translation_key'
            ],
            array_filter($findTranslationKeys)
        );
        $this->assertEquals(
            [
                'test_workflow_transition_2_translation_key',
                'test_workflow_transition_2_translation_button_label_key',
                'test_workflow_transition_2_translation_button_title_key',
                'test_workflow_transition_2_translation_message_key'
            ],
            $removeTranslationKeys
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Previous WorkflowDefinition expected, got null.
     */
    public function testClearLogicExceptionOnAbsentPreviousDefinition()
    {
        $this->translationKeysSubscriber->clearTranslationKeys(
            new WorkflowChangesEvent(new WorkflowDefinition(), null)
        );
    }

    public function testDeleteTranslationKeys()
    {
        $deletedDefinition = (new WorkflowDefinition())->setName('test_workflow')->setLabel('label_translation_key');

        $this->translationManager->expects($this->once())
            ->method('removeTranslationKey')
            ->with('label_translation_key');
        $this->translationManager->expects($this->once())->method('flush');

        $this->translationKeysSubscriber->deleteTranslationKeys(new WorkflowChangesEvent($deletedDefinition));
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
