<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Form\EventListener\EmailNotificationTypeListener;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionNotificationSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionSelectType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\EmailNotificationStub as EmailNotification;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailNotificationTypeListenerTest extends TestCase
{
    use EntityTrait;

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var EmailNotificationTypeListener */
    private $listener;

    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);

        $this->form = $this->createMock(FormInterface::class);
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['eventName', $this->createMock(FormInterface::class)],
                ['template', $this->createMock(FormInterface::class)]
            ]);

        $this->listener = new EmailNotificationTypeListener($this->workflowRegistry);
    }

    public function testOnPostSetDataInvalidData()
    {
        $event = $this->getEvent();

        $this->form->expects($this->never())
            ->method($this->anything());

        $this->workflowRegistry->expects($this->never())
            ->method($this->anything());

        $this->listener->onPostSetData($event);
    }

    public function testOnPostSetDataUnsupportedData()
    {
        $event = $this->getEvent(new EmailNotification());

        $this->form->expects($this->never())
            ->method($this->anything());

        $this->workflowRegistry->expects($this->never())
            ->method($this->anything());

        $this->listener->onPostSetData($event);
    }

    /**
     * @dataProvider onPostSetDataWithoutWorkflowProvider
     */
    public function testOnPostSetDataWithoutWorkflow(EmailNotification $data, EmailNotification $expected)
    {
        $event = $this->getEvent($data);

        $this->assertEventFieldUpdated();

        $this->workflowRegistry->expects($this->once())
            ->method('hasWorkflowsByEntityClass')
            ->with($data->getEntityName())
            ->willReturn(false);

        $this->listener->onPostSetData($event);

        $this->assertEquals($expected, $event->getData());
    }

    public function onPostSetDataWithoutWorkflowProvider(): \Generator
    {
        yield 'with workflow event' => [
            'data' => $this->getEntity(
                EmailNotification::class,
                ['entityName' => \stdClass::class, 'eventName' => WorkflowEvents::NOTIFICATION_TRANSIT_EVENT]
            ),
            'expected' => $this->getEntity(EmailNotification::class, ['entityName' => \stdClass::class])
        ];

        yield 'with not workflow event' => [
            'data' => $this->getEntity(
                EmailNotification::class,
                ['entityName' => \stdClass::class, 'eventName' => 'test']
            ),
            'expected' => $this->getEntity(
                EmailNotification::class,
                ['entityName' => \stdClass::class, 'eventName' => 'test']
            )
        ];
    }

    private function assertEventFieldUpdated()
    {
        $choices =['test_1', 'test2'];
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->once())
            ->method('getOption')
            ->with('choices')
            ->willReturn($choices);
        $config->expects($this->once())
            ->method('getOptions')
            ->willReturn(['choices' => $choices]);

        $formType = $this->createMock(FormTypeInterface::class);
        $resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $resolvedFormType->expects($this->once())
            ->method('getInnerType')
            ->willReturn($formType);
        $config->expects($this->once())
            ->method('getType')
            ->willReturn($resolvedFormType);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $eventForm */
        $eventForm = $this->form->get('eventName');
        $eventForm->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testOnPostSetData(bool $hasWorkflowDefinition, bool $hasWorkflowTransitionName, array $expected)
    {
        $data = $this->getEntity(
            EmailNotification::class,
            [
                'entityName' => \stdClass::class,
                'eventName' => WorkflowEvents::NOTIFICATION_TRANSIT_EVENT,
                'workflowDefinition' => $this->getEntity(WorkflowDefinition::class, ['name' => 'test_workflow'])
            ]
        );

        $event = $this->getEvent($data);

        $this->form->expects($this->once())
            ->method('remove')
            ->with('template');

        $forms = $this->assertFormUpdate($hasWorkflowDefinition, $hasWorkflowTransitionName);

        $this->workflowRegistry->expects($this->once())
            ->method('hasWorkflowsByEntityClass')
            ->with($data->getEntityName())
            ->willReturn(true);

        $this->listener->onPostSetData($event);

        $this->assertEquals(
            array_merge(
                $expected,
                [
                    [
                        'form' => $this->form->get('template'),
                        'type' => null,
                        'options' => []
                    ]
                ]
            ),
            $forms->toArray()
        );
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testOnPreSubmit(bool $hasWorkflowDefinition, bool $hasWorkflowTransitionName, array $expected)
    {
        $event = $this->getEvent(['entityName' => \stdClass::class, 'workflow_definition' => 'test_workflow']);

        $forms = $this->assertFormUpdate($hasWorkflowDefinition, $hasWorkflowTransitionName);

        $this->listener->onPreSubmit($event);

        $this->assertEquals($expected, $forms->toArray());
    }

    public function formDataProvider(): \Generator
    {
        $workflow = [
            'form' => 'workflow_definition',
            'type' => WorkflowDefinitionNotificationSelectType::class,
            'options' => [
                'label' => 'workflow',
                'required' => true,
                'placeholder' => '',
                'constraints' => [new NotBlank()],
                'configs' => ['allowClear' => true, 'placeholder' => 'oro.workflow.form.choose_workflow'],
                'attr' => ['autocomplete' => 'off'],
                'entityClass' => \stdClass::class
            ]
        ];

        $transition = [
            'form' => 'workflow_transition_name',
            'type' => WorkflowTransitionSelectType::class,
            'options' => [
                'label' => 'transition',
                'required' => true,
                'placeholder' => '',
                'constraints' => [new NotBlank()],
                'configs' => ['allowClear' => true, 'placeholder' => 'oro.workflow.form.choose_transition'],
                'workflowName' => 'test_workflow'
            ]
        ];

        yield 'has no workflow fields' => [
            'hasWorkflowDefinition' => false,
            'hasWorkflowTransitionName' => false,
            'expected' => [$workflow, $transition]
        ];

        yield 'has workflow_definition' => [
            'hasWorkflowDefinition' => true,
            'hasWorkflowTransitionName' => false,
            'expected' => [$transition]
        ];

        yield 'has workflow_transition_name' => [
            'hasWorkflowDefinition' => false,
            'hasWorkflowTransitionName' => true,
            'expected' => [$workflow]
        ];
    }

    /**
     * @dataProvider onPreSubmitUnsupportedDataProvider
     */
    public function testOnPreSubmitUnsupportedData(array $data)
    {
        $event = $this->getEvent($data);

        $forms = $this->assertFormUpdate(false, false);

        $this->listener->onPreSubmit($event);

        $this->assertEquals([], $forms->toArray());
    }

    public function onPreSubmitUnsupportedDataProvider(): \Generator
    {
        yield 'unsupported entity name' => [
            'data' => ['entityName' => null, 'workflow_definition' => 'test_workflow']
        ];

        yield 'unsupported workflow' => [
            'data' => ['entityName' => \stdClass::class, 'workflow_definition' => null]
        ];
    }

    private function assertFormUpdate(bool $hasWorkflowDefinition, bool $hasWorkflowTransitionName): ArrayCollection
    {
        $this->form->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['workflow_definition', $hasWorkflowDefinition],
                ['workflow_transition_name', $hasWorkflowTransitionName],
            ]);

        $forms = new ArrayCollection();

        $this->form->expects($this->any())
            ->method('add')
            ->willReturnCallback(function ($name, $type = null, array $options = []) use ($forms) {
                $forms->add(['form' => $name, 'type' => $type, 'options' => $options]);
            });

        return $forms;
    }

    private function getEvent(mixed $data = null): FormEvent
    {
        $this->form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->any())
            ->method('getForm')
            ->willReturn($this->form);
        $event->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        return $event;
    }
}
