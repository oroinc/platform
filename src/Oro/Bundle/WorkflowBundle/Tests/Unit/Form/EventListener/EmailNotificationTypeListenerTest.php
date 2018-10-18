<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\EventListener\EmailNotificationTypeListener;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionNotificationSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionSelectType;
use Oro\Bundle\WorkflowBundle\Migrations\Data\ORM\LoadWorkflowNotificationEvents;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\EmailNotificationStub as EmailNotification;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailNotificationTypeListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowRegistry;

    /** @var EmailNotificationTypeListener */
    protected $listener;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $form;

    protected function setUp()
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);

        $this->listener = new EmailNotificationTypeListener($this->workflowRegistry);

        $this->form = $this->createMock(FormInterface::class);
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['event', $this->createMock(FormInterface::class)],
                    ['template', $this->createMock(FormInterface::class)]
                ]
            );
    }

    public function testOnPostSetDataInvalidData()
    {
        $event = $this->getEvent();

        $this->form->expects($this->never())->method($this->anything());

        $this->workflowRegistry->expects($this->never())->method($this->anything());

        $this->listener->onPostSetData($event);
    }

    public function testOnPostSetDataUnsupportedData()
    {
        $event = $this->getEvent(new EmailNotification());

        $this->form->expects($this->never())->method($this->anything());

        $this->workflowRegistry->expects($this->never())->method($this->anything());

        $this->listener->onPostSetData($event);
    }

    /**
     * @dataProvider onPostSetDataWithoutWorkflowProvider
     *
     * @param EmailNotification $data
     * @param EmailNotification $expected
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

    /**
     * @return \Generator
     */
    public function onPostSetDataWithoutWorkflowProvider()
    {
        yield 'with workflow event' => [
            'data' => $this->getEntity(
                EmailNotification::class,
                ['entityName' => \stdClass::class, 'event' => new Event(LoadWorkflowNotificationEvents::TRANSIT_EVENT)]
            ),
            'expected' => $this->getEntity(EmailNotification::class, ['entityName' => \stdClass::class])
        ];

        yield 'with not workflow event' => [
            'data' => $this->getEntity(
                EmailNotification::class,
                ['entityName' => \stdClass::class, 'event' => new Event('test')]
            ),
            'expected' => $this->getEntity(
                EmailNotification::class,
                ['entityName' => \stdClass::class, 'event' => new Event('test')]
            )
        ];
    }

    protected function assertEventFieldUpdated()
    {
        /** @var Expr|\PHPUnit\Framework\MockObject\MockObject $expr */
        $expr = $this->createMock(Expr::class);
        $expr->expects($this->once())->method('neq')->with('c.name', ':event')->willReturnSelf();

        /** @var FormConfigInterface|\PHPUnit\Framework\MockObject\MockObject $config */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('expr')->willReturn($expr);
        $qb->expects($this->once())->method('andWhere')->with($expr)->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('event', LoadWorkflowNotificationEvents::TRANSIT_EVENT);

        /** @var FormConfigInterface|\PHPUnit\Framework\MockObject\MockObject $config */
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->once())->method('getOption')->with('query_builder')->willReturn($qb);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $eventForm */
        $eventForm = $this->form->get('event');
        $eventForm->expects($this->once())->method('getConfig')->willReturn($config);
    }

    /**
     * @dataProvider formDataProvider
     *
     * @param bool $hasWorkflowDefinition
     * @param bool $hasWorkflowTransitionName
     * @param array $expected
     */
    public function testOnPostSetData($hasWorkflowDefinition, $hasWorkflowTransitionName, array $expected)
    {
        $data = $this->getEntity(
            EmailNotification::class,
            [
                'entityName' => \stdClass::class,
                'event' => new Event(LoadWorkflowNotificationEvents::TRANSIT_EVENT),
                'workflowDefinition' => $this->getEntity(WorkflowDefinition::class, ['name' => 'test_workflow'])
            ]
        );

        $event = $this->getEvent($data);

        $this->form->expects($this->once())->method('remove')->with('template');

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
     *
     * @param bool $hasWorkflowDefinition
     * @param bool $hasWorkflowTransitionName
     * @param array $expected
     */
    public function testOnPreSubmit($hasWorkflowDefinition, $hasWorkflowTransitionName, array $expected)
    {
        $event = $this->getEvent(['entityName' => \stdClass::class, 'workflow_definition' => 'test_workflow']);

        $forms = $this->assertFormUpdate($hasWorkflowDefinition, $hasWorkflowTransitionName);

        $this->listener->onPreSubmit($event);

        $this->assertEquals($expected, $forms->toArray());
    }

    /**
     * @return \Generator
     */
    public function formDataProvider()
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
     *
     * @param array $data
     */
    public function testOnPreSubmitUnsupportedData(array $data)
    {
        $event = $this->getEvent($data);

        $forms = $this->assertFormUpdate(false, false);

        $this->listener->onPreSubmit($event);

        $this->assertEquals([], $forms->toArray());
    }

    /**
     * @return \Generator
     */
    public function onPreSubmitUnsupportedDataProvider()
    {
        yield 'unsupported entity name' => [
            'data' => ['entityName' => null, 'workflow_definition' => 'test_workflow']
        ];

        yield 'unsupported workflow' => [
            'data' => ['entityName' => \stdClass::class, 'workflow_definition' => null]
        ];
    }

    /**
     * @param bool $hasWorkflowDefinition
     * @param bool $hasWorkflowTransitionName
     * @return ArrayCollection
     */
    protected function assertFormUpdate($hasWorkflowDefinition, $hasWorkflowTransitionName)
    {
        $this->form->expects($this->any())
            ->method('has')
            ->willReturnMap(
                [
                    ['workflow_definition', $hasWorkflowDefinition],
                    ['workflow_transition_name', $hasWorkflowTransitionName],
                ]
            );

        $forms = new ArrayCollection();

        $this->form->expects($this->any())
            ->method('add')
            ->willReturnCallback(
                function ($name, $type = null, array $options = []) use ($forms) {
                    $forms->add(['form' => $name, 'type' => $type, 'options' => $options]);
                }
            );

        return $forms;
    }

    /**
     * @param mixed $data
     * @return \PHPUnit\Framework\MockObject\MockObject|FormEvent
     */
    protected function getEvent($data = null)
    {
        $this->form->expects($this->any())->method('getData')->willReturn($data);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->any())->method('getForm')->willReturn($this->form);
        $event->expects($this->any())->method('getData')->willReturn($data);

        return $event;
    }
}
