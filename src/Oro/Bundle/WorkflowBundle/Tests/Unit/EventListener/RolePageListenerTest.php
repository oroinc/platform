<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\WorkflowBundle\EventListener\RolePageListener;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RolePageListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack */
    private $requestStack;

    /** @var RolePageListener */
    private $listener;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($value) {
                return 'translated: ' . $value;
            });

        $this->listener = new RolePageListener($translator, $this->requestStack);
    }

    public function testOnUpdatePageRenderWithoutRequest()
    {
        $event = new BeforeFormRenderEvent(
            $this->createMock(FormView::class),
            [],
            $this->createMock(Environment::class),
            null
        );

        $this->listener->onUpdatePageRender($event);

        $this->assertEquals([], $event->getFormData());
    }

    public function testOnUpdatePageRenderOnWrongPage()
    {
        $event = new BeforeFormRenderEvent(
            $this->createMock(FormView::class),
            [],
            $this->createMock(Environment::class),
            null
        );

        $this->requestStack->push(new Request([], [], ['_route' => 'some_route']));

        $this->listener->onUpdatePageRender($event);

        $this->assertEquals([], $event->getFormData());
    }

    public function testOnUpdatePageRenderOnNonCloneRolePage()
    {
        $event = new BeforeFormRenderEvent(
            $this->createMock(FormView::class),
            [],
            $this->createMock(Environment::class),
            null
        );

        $this->requestStack->push(
            new Request(
                [],
                [],
                ['_route' => 'oro_action_widget_form', '_route_params' => ['operationName' => 'some_operation']]
            )
        );

        $this->listener->onUpdatePageRender($event);

        $this->assertEquals([], $event->getFormData());
    }

    /**
     * @dataProvider onUpdatePageRenderRoutesProvider
     */
    public function testOnUpdatePageRenderWithEntityInEvent(string $routeName, array $routeParameters = [])
    {
        $entity = new Role();
        $form = new FormView();
        $form->vars['value'] = new \stdClass();
        $twig = $this->createMock(Environment::class);
        $event = new BeforeFormRenderEvent(
            $form,
            [
                'dataBlocks' => [
                    ['first block'],
                    'second' => ['second block'],
                    ['third block']
                ]
            ],
            $twig,
            $entity
        );

        $renderedHtml = '<div>Rendered datagrid position</div>';
        $twig->expects($this->once())
            ->method('render')
            ->with(
                '@OroWorkflow/Datagrid/aclGrid.html.twig',
                [
                    'entity'     => $entity,
                    'isReadonly' => false
                ]
            )
            ->willReturn($renderedHtml);

        $this->requestStack->push(new Request([], [], ['_route' => $routeName, '_route_params' => $routeParameters]));

        $this->listener->onUpdatePageRender($event);

        $data = $event->getFormData();
        $this->assertCount(4, $data['dataBlocks']);
        $this->assertEquals([0 => 0, 1 => 'second', 2 => 1, 3 => 2], array_keys($data['dataBlocks']));
        $workflowBlock = $data['dataBlocks'][2];
        $this->assertEquals(
            'translated: oro.workflow.workflowdefinition.entity_plural_label',
            $workflowBlock['title']
        );
        $this->assertEquals(
            [['data' => [$renderedHtml]]],
            $workflowBlock['subblocks']
        );
    }

    /**
     * @dataProvider onUpdatePageRenderRoutesProvider
     */
    public function testOnUpdatePageRenderWithoutEntityInEvent(string $routeName, array $routeParameters = [])
    {
        $entity = new Role();
        $form = new FormView();
        $form->vars['value'] = $entity;
        $twig = $this->createMock(Environment::class);
        $event = new BeforeFormRenderEvent(
            $form,
            [
                'dataBlocks' => [
                    ['first block'],
                    'second' => ['second block'],
                    ['third block']
                ]
            ],
            $twig,
            null
        );

        $renderedHtml = '<div>Rendered datagrid position</div>';
        $twig->expects($this->once())
            ->method('render')
            ->with(
                '@OroWorkflow/Datagrid/aclGrid.html.twig',
                [
                    'entity'     => $entity,
                    'isReadonly' => false
                ]
            )
            ->willReturn($renderedHtml);

        $this->requestStack->push(new Request([], [], ['_route' => $routeName, '_route_params' => $routeParameters]));

        $this->listener->onUpdatePageRender($event);

        $data = $event->getFormData();
        $this->assertCount(4, $data['dataBlocks']);
        $this->assertEquals([0 => 0, 1 => 'second', 2 => 1, 3 => 2], array_keys($data['dataBlocks']));
        $workflowBlock = $data['dataBlocks'][2];
        $this->assertEquals(
            'translated: oro.workflow.workflowdefinition.entity_plural_label',
            $workflowBlock['title']
        );
        $this->assertEquals(
            [['data' => [$renderedHtml]]],
            $workflowBlock['subblocks']
        );
    }

    public function onUpdatePageRenderRoutesProvider(): array
    {
        return [
            ['oro_user_role_update'],
            ['oro_user_role_create'],
            ['oro_action_widget_form', ['operationName' => 'clone_role']],
        ];
    }

    public function testOnViewPageRenderWithoutRequest()
    {
        $event = new BeforeViewRenderEvent(
            $this->createMock(Environment::class),
            [],
            new \stdClass()
        );

        $this->listener->onViewPageRender($event);

        $this->assertEquals([], $event->getData());
    }

    public function testOnViewPageRenderOnNonUpdateRolePage()
    {
        $event = new BeforeViewRenderEvent(
            $this->createMock(Environment::class),
            [],
            new \stdClass()
        );

        $this->requestStack->push(new Request([], [], ['_route' => 'some_route']));

        $this->listener->onViewPageRender($event);

        $this->assertEquals([], $event->getData());
    }

    public function testOnViewPageRender()
    {
        $entity = new Role();
        $twig = $this->createMock(Environment::class);
        $event = new BeforeViewRenderEvent(
            $twig,
            [
                'dataBlocks' => [
                    ['first block'],
                    ['second block'],
                    ['third block']
                ]
            ],
            $entity
        );

        $renderedHtml = '<div>Rendered datagrid position</div>';
        $twig->expects($this->once())
            ->method('render')
            ->with(
                '@OroWorkflow/Datagrid/aclGrid.html.twig',
                [
                    'entity'     => $entity,
                    'isReadonly' => true
                ]
            )
            ->willReturn($renderedHtml);

        $this->requestStack->push(new Request([], [], ['_route' => 'oro_user_role_view']));

        $this->listener->onViewPageRender($event);

        $data = $event->getData();
        $this->assertCount(4, $data['dataBlocks']);
        $this->assertEquals([0, 1, 2, 3], array_keys($data['dataBlocks']));
        $workflowBlock = $data['dataBlocks'][3];
        $this->assertEquals(
            'translated: oro.workflow.workflowdefinition.entity_plural_label',
            $workflowBlock['title']
        );
        $this->assertEquals(
            [['data' => [$renderedHtml]]],
            $workflowBlock['subblocks']
        );
    }
}
