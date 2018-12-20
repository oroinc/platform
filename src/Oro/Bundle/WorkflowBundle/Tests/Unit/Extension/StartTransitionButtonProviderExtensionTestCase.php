<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\StubButton;
use Oro\Bundle\WorkflowBundle\Button\StartTransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

abstract class StartTransitionButtonProviderExtensionTestCase extends AbstractTransitionButtonProviderExtensionTestCase
{
    const ENTITY_CLASS = 'entity1';
    const ROUTE_NAME = 'route1';
    const DATAGRID = 'datagrid1';

    /**
     * @dataProvider findDataProvider
     *
     * @param bool $expected
     * @param string|null $entityClass
     * @param string|null $routeName
     * @param string|null $datagrid
     */
    public function testFind($expected, $entityClass = null, $routeName = null, $datagrid = null)
    {
        $this->applicationProvider->expects($this->atLeastOnce())
            ->method('getCurrentApplication')
            ->willReturn($expected ? $this->getApplication() : null);

        $buttons = [];

        if ($expected) {
            $transition = $this->getTransition('transition1')
                ->setInitEntities($entityClass ? [$entityClass] : [])
                ->setInitRoutes($routeName ? [$routeName] : [])
                ->setInitDatagrids($datagrid ? [$datagrid] : []);

            $transitionManager = $this->getTransitionManager([$transition], 'getStartTransitions');

            $workflow = $this->getWorkflow(
                $transitionManager,
                [
                    'init_entities' => [
                        self::ENTITY_CLASS => ['transition1', 'transition2'],
                    ],
                    'init_datagrids' => [
                        self::DATAGRID => ['transition1', 'transition2'],
                    ],
                    'init_routes' => [
                        self::ROUTE_NAME => ['transition1', 'transition2'],
                    ],
                ]
            );

            $this->workflowRegistry->expects($this->once())
                ->method('getActiveWorkflows')
                ->willReturn(new ArrayCollection([$workflow]));

            $buttonContext = (new ButtonContext())
                ->setEntity($entityClass)
                ->setRouteName($routeName)
                ->setOriginalUrl('example.com')
                ->setDatagridName($datagrid);

            $this->originalUrlProvider->expects($this->once())->method('getOriginalUrl')->willReturn('example.com');

            $buttons = [new StartTransitionButton($transition, $workflow, $buttonContext)];
        } else {
            $this->originalUrlProvider->expects($this->never())->method('getOriginalUrl');
        }

        $this->assertEquals(
            $buttons,
            $this->extension->find(
                (new ButtonSearchContext())->setEntity($entityClass)->setRouteName($routeName)->setDatagrid($datagrid)
            )
        );
    }

    /**
     * @return array
     */
    public function findDataProvider()
    {
        return [
            'entity' => [
                'expected' => true,
                'entityClass' => self::ENTITY_CLASS,
            ],
            'route' => [
                'expected' => true,
                'entityClass' => null,
                'routeName' => self::ROUTE_NAME,
            ],
            'datagrid' => [
                'expected' => true,
                'entityClass' => null,
                'routeName' => null,
                'datagrid' => self::DATAGRID,
            ],
            'not matched' => [
                'expected' => false,
                'entityClass' => 'test_entity',
            ],
        ];
    }

    public function testFindWithExclusiveRecordGroups()
    {
        $configuration = [
            'init_entities' => [
                'entity1' => ['transition1', 'transition2', 'transition3', 'transition4', 'transition5']
            ]
        ];

        $workflow1 = $this->getWorkflow(
            $this->getTransitionManager(
                [$this->getTransition('transition1'), $this->getTransition('transition2')],
                'getStartTransitions'
            ),
            $configuration,
            ['group1', 'group2']
        );

        $workflow2 = $this->getWorkflow(
            $this->getTransitionManager(
                [$this->getTransition('transition3'), $this->getTransition('transition4')],
                'getStartTransitions'
            ),
            $configuration,
            ['group2', 'group3']
        );

        $workflow3 = $this->getWorkflow(
            $this->getTransitionManager(
                [$this->getTransition('transition5'), $this->getTransition('transition6')],
                'getStartTransitions'
            ),
            $configuration,
            ['group3', 'group4']
        );

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflows')
            ->willReturn(new ArrayCollection([$workflow1, $workflow2, $workflow3]));

        $buttonContext = new ButtonContext();
        $buttonContext->setEntity('entity1');

        $this->applicationProvider->expects($this->atLeastOnce())
            ->method('getCurrentApplication')
            ->willReturn($this->getApplication());

        $this->assertEquals(
            [
                new StartTransitionButton($this->getTransition('transition1'), $workflow1, $buttonContext),
                new StartTransitionButton($this->getTransition('transition2'), $workflow1, $buttonContext),
                new StartTransitionButton($this->getTransition('transition5'), $workflow3, $buttonContext)
            ],
            $this->extension->find((new ButtonSearchContext())->setEntity('entity1'))
        );
    }

    public function testFindWithGroupAtContext()
    {
        $this->workflowRegistry->expects($this->never())->method('getActiveWorkflows');
        $this->assertEquals(
            [],
            $this->extension->find((new ButtonSearchContext())->setGroup('test_group'))
        );
    }

    /**
     * @dataProvider isAvailableDataProvider
     *
     * @param ButtonInterface $button
     * @param bool $expected
     */
    public function testIsAvailable(ButtonInterface $button, $expected)
    {
        $this->assertEquals($expected, $this->extension->isAvailable($button, new ButtonSearchContext()));
    }

    /**
     * @return array
     */
    public function isAvailableDataProvider()
    {
        return [
            'available' => [
                'button' => $this->createTransitionButton(true),
                'expected' => true
            ],
            'not available' => [
                'button' => $this->createTransitionButton(false),
                'expected' => false
            ]
        ];
    }

    public function testIsAvailableException()
    {
        $stubButton = new StubButton();

        $this->expectException(UnsupportedButtonException::class);
        $this->expectExceptionMessage(sprintf(
            '%s is not supported by %s. Can not determine availability',
            StubButton::class,
            get_class($this->extension)
        ));

        $this->extension->isAvailable($stubButton, new ButtonSearchContext());
    }

    public function testSupports()
    {
        // for start transition
        $this->assertTrue($this->extension->supports($this->createTransitionButton()));
        // for notstart transition
        $this->assertFalse($this->extension->supports($this->createTransitionButton(false, false)));

        /** @var ButtonInterface|\PHPUnit\Framework\MockObject\MockObject $notTransitionButton */
        $notTransitionButton = $this->createMock(ButtonInterface::class);
        // for not supported button
        $this->assertFalse($this->extension->supports($notTransitionButton));
    }

    /**
     * @param TransitionManager|\PHPUnit\Framework\MockObject\MockObject $transitionManager
     * @param array $configuration
     * @param array $exclusiveRecordGroups
     *
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getWorkflow(
        TransitionManager $transitionManager,
        array $configuration = [],
        array $exclusiveRecordGroups = []
    ) {
        /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject $workflow */
        $workflow = $this->getMockBuilder(Workflow::class)
            ->setMethods(['getTransitionManager', 'getVariables'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject $definition */
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())->method('getRelatedEntity')->willReturn(self::ENTITY_CLASS);
        $definition->expects($this->any())->method('getConfiguration')->willReturn($configuration);
        $definition->expects($this->any())->method('getExclusiveRecordGroups')->willReturn($exclusiveRecordGroups);

        $workflow->setDefinition($definition);
        $workflow->expects($this->any())->method('getTransitionManager')->willReturn($transitionManager);
        $workflow->expects($this->any())->method('getVariables')->willReturn(new ArrayCollection());

        return $workflow;
    }

    /**
     * @param bool $isAvailable
     * @param bool $isStart
     *
     * @return StartTransitionButton
     */
    private function createTransitionButton($isAvailable = false, $isStart = true)
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())->method('isAvailable')->willReturn($isAvailable);
        $transition->expects($this->any())->method('isStart')->willReturn($isStart);
        $transitionManager = $this->createMock(TransitionManager::class);

        $workflow = $this->getWorkflow($transitionManager);

        return new StartTransitionButton($transition, $workflow, new ButtonContext());
    }
}
