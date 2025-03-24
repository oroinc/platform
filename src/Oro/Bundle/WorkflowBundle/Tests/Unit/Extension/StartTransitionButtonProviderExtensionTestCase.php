<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\ActionBundle\Provider\OriginalUrlProvider;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\StubButton;
use Oro\Bundle\WorkflowBundle\Button\StartTransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Extension\AbstractButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class StartTransitionButtonProviderExtensionTestCase extends TestCase
{
    private const ENTITY_CLASS = 'entity1';
    private const ROUTE_NAME = 'route1';
    private const DATAGRID = 'datagrid1';

    protected WorkflowRegistry&MockObject $workflowRegistry;
    protected RouteProviderInterface&MockObject $routeProvider;
    protected OriginalUrlProvider&MockObject $originalUrlProvider;
    protected CurrentApplicationProviderInterface&MockObject $applicationProvider;
    protected TransitionOptionsResolver&MockObject $optionsResolver;
    protected EventDispatcher&MockObject $eventDispatcher;
    protected TranslatorInterface&MockObject $translator;
    protected AbstractButtonProviderExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->routeProvider = $this->createMock(RouteProviderInterface::class);
        $this->originalUrlProvider = $this->createMock(OriginalUrlProvider::class);
        $this->applicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);
        $this->optionsResolver = $this->createMock(TransitionOptionsResolver::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = $this->createExtension();
        $this->extension->setApplicationProvider($this->applicationProvider);
    }

    abstract protected function createExtension(): AbstractButtonProviderExtension;

    abstract protected function getApplication(): string;

    private function getTransitionManager(array $transitions, string $method): TransitionManager
    {
        $manager = $this->createMock(TransitionManager::class);
        $manager->expects(self::any())
            ->method($method)
            ->willReturn(new ArrayCollection($transitions));

        return $manager;
    }

    private function getTransition(string $name): Transition
    {
        $transition = new Transition($this->optionsResolver, $this->eventDispatcher, $this->translator);
        $transition->setName($name);

        return $transition;
    }

    /**
     * @dataProvider findDataProvider
     */
    public function testFind(
        bool $expected,
        ?string $entityClass = null,
        string $routeName = '',
        ?string $datagrid = null
    ): void {
        $this->applicationProvider->expects(self::atLeastOnce())
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

            $this->workflowRegistry->expects(self::once())
                ->method('getActiveWorkflows')
                ->willReturn(new ArrayCollection([$workflow]));

            $buttonContext = (new ButtonContext())
                ->setEntity($entityClass)
                ->setRouteName($routeName)
                ->setOriginalUrl('example.com')
                ->setDatagridName($datagrid);

            $this->originalUrlProvider->expects(self::once())
                ->method('getOriginalUrl')
                ->willReturn('example.com');

            $buttons = [new StartTransitionButton($transition, $workflow, $buttonContext)];
        } else {
            $this->originalUrlProvider->expects(self::never())
                ->method('getOriginalUrl');
        }

        self::assertEquals(
            $buttons,
            $this->extension->find(
                (new ButtonSearchContext())->setEntity($entityClass)->setRouteName($routeName)->setDatagrid($datagrid)
            )
        );
    }

    public function findDataProvider(): array
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
                'routeName' => '',
                'datagrid' => self::DATAGRID,
            ],
            'not matched' => [
                'expected' => false,
                'entityClass' => 'test_entity',
            ],
        ];
    }

    public function testFindWithExclusiveRecordGroups(): void
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

        $this->workflowRegistry->expects(self::once())
            ->method('getActiveWorkflows')
            ->willReturn(new ArrayCollection([$workflow1, $workflow2, $workflow3]));

        $buttonContext = new ButtonContext();
        $buttonContext->setEntity('entity1');

        $this->applicationProvider->expects(self::atLeastOnce())
            ->method('getCurrentApplication')
            ->willReturn($this->getApplication());

        self::assertEquals(
            [
                new StartTransitionButton($this->getTransition('transition1'), $workflow1, $buttonContext),
                new StartTransitionButton($this->getTransition('transition2'), $workflow1, $buttonContext),
                new StartTransitionButton($this->getTransition('transition5'), $workflow3, $buttonContext)
            ],
            $this->extension->find((new ButtonSearchContext())->setEntity('entity1'))
        );
    }

    public function testFindWithGroupAtContext(): void
    {
        $this->workflowRegistry->expects(self::never())
            ->method('getActiveWorkflows');
        self::assertEquals(
            [],
            $this->extension->find((new ButtonSearchContext())->setGroup('test_group'))
        );
    }

    /**
     * @dataProvider isAvailableDataProvider
     */
    public function testIsAvailable(ButtonInterface $button, bool $expected): void
    {
        self::assertEquals($expected, $this->extension->isAvailable($button, new ButtonSearchContext()));
    }

    public function isAvailableDataProvider(): array
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

    public function testIsAvailableException(): void
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

    public function testSupports(): void
    {
        // for start transition
        self::assertTrue($this->extension->supports($this->createTransitionButton()));
        // for not start transition
        self::assertFalse($this->extension->supports($this->createTransitionButton(false, false)));

        $notTransitionButton = $this->createMock(ButtonInterface::class);
        // for not supported button
        self::assertFalse($this->extension->supports($notTransitionButton));
    }

    private function getWorkflow(
        TransitionManager $transitionManager,
        array $configuration = [],
        array $exclusiveRecordGroups = []
    ): Workflow {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->onlyMethods(['getTransitionManager', 'getVariables'])
            ->disableOriginalConstructor()
            ->getMock();

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(self::any())
            ->method('getRelatedEntity')
            ->willReturn(self::ENTITY_CLASS);
        $definition->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);
        $definition->expects(self::any())
            ->method('getExclusiveRecordGroups')
            ->willReturn($exclusiveRecordGroups);

        $workflow->setDefinition($definition);
        $workflow->expects(self::any())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);
        $workflow->expects(self::any())
            ->method('getVariables')
            ->willReturn(new ArrayCollection());

        return $workflow;
    }

    private function createTransitionButton(bool $isAvailable = false, bool $isStart = true): StartTransitionButton
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects(self::any())
            ->method('isAvailable')
            ->willReturn($isAvailable);
        $transition->expects(self::any())
            ->method('isStart')
            ->willReturn($isStart);
        $transitionManager = $this->createMock(TransitionManager::class);

        $workflow = $this->getWorkflow($transitionManager);

        return new StartTransitionButton($transition, $workflow, new ButtonContext());
    }
}
