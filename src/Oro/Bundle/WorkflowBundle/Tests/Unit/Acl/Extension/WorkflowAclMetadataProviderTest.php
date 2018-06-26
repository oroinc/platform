<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclMetadata;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclMetadataProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\Translation\TranslatorInterface;

class WorkflowAclMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    /** @var WorkflowAclMetadataProvider */
    protected $workflowAclMetadataProvider;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowAclMetadataProvider = new WorkflowAclMetadataProvider(
            $this->doctrine,
            $this->translator,
            $this->featureChecker
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function loadWorkflowExpectations()
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(WorkflowDefinition::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->with('w')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('select')
            ->with('w.name, w.label, w.configuration')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        return $query;
    }

    public function testGetMetadata()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps'       => [
                'step1'     => ['allowed_transitions' => ['transition1'], 'label' => 'step 1'],
                'next_step' => ['label' => 'next step']
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1', 'step_to' => 'next_step']
            ]
        ];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label, $parameters, $domain) {
                $result = 'translated: ' . $label;
                if (!empty($parameters)) {
                    foreach ($parameters as $key => $val) {
                        $result .= ' ' . $key . ': (' . $val . ')';
                    }
                }
                if (!empty($domain)) {
                    $result .= ' [domain: ' . $domain . ']';
                }

                return $result;
            });

        $expectedTransition = new FieldSecurityMetadata(
            'transition1|step1|next_step',
            'translated: transition 1 [domain: workflows] '
            . "(translated: step 1 [domain: workflows] \u{2192} translated: next step [domain: workflows])"
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1 [domain: workflows]',
            '',
            [$expectedTransition]
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
        // test that the local cache is used
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testGetMetadataForStepWithoutTransitions()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps'       => [
                'step1'     => ['label' => 'step 1'],
                'next_step' => ['label' => 'next step']
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1', 'step_to' => 'next_step']
            ]
        ];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label, $parameters, $domain) {
                $result = 'translated: ' . $label;
                if (!empty($parameters)) {
                    foreach ($parameters as $key => $val) {
                        $result .= ' ' . $key . ': (' . $val . ')';
                    }
                }
                if (!empty($domain)) {
                    $result .= ' [domain: ' . $domain . ']';
                }

                return $result;
            });

        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1 [domain: workflows]',
            '',
            []
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testGetMetadataForStartTransition()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps'       => [
                'step1'     => ['label' => 'step 1'],
                'next_step' => ['label' => 'next step']
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1', 'step_to' => 'next_step', 'is_start' => true]
            ]
        ];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label, $parameters, $domain) {
                $result = 'translated: ' . $label;
                if (!empty($parameters)) {
                    foreach ($parameters as $key => $val) {
                        $result .= ' ' . $key . ': (' . $val . ')';
                    }
                }
                if (!empty($domain)) {
                    $result .= ' [domain: ' . $domain . ']';
                }

                return $result;
            });

        $expectedTransition = new FieldSecurityMetadata(
            'transition1||next_step',
            'translated: transition 1 [domain: workflows] '
            . "(translated: (Start) [domain: jsmessages] \u{2192} translated: next step [domain: workflows])"
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1 [domain: workflows]',
            '',
            [$expectedTransition]
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testGetMetadataForStartTransitionWithPredefinedName()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps'       => [
                'next_step' => ['label' => 'next step']
            ],
            'transitions' => [
                '__start__' => ['label' => 'transition 1', 'step_to' => 'next_step']
            ]
        ];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label, $parameters, $domain) {
                $result = 'translated: ' . $label;
                if (!empty($parameters)) {
                    foreach ($parameters as $key => $val) {
                        $result .= ' ' . $key . ': (' . $val . ')';
                    }
                }
                if (!empty($domain)) {
                    $result .= ' [domain: ' . $domain . ']';
                }

                return $result;
            });

        $expectedTransition = new FieldSecurityMetadata(
            '__start__||next_step',
            'translated: transition 1 [domain: workflows] '
            . "(translated: (Start) [domain: jsmessages] \u{2192} translated: next step [domain: workflows])"
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1 [domain: workflows]',
            '',
            [$expectedTransition]
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testGetMetadataWhenToStepDoesNotExist()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps'       => [
                'step1' => ['allowed_transitions' => ['transition1'], 'label' => 'step 1']
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1', 'step_to' => 'next_step']
            ]
        ];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->with(self::isType('string'), [], 'workflows')
            ->willReturnCallback(function ($label) {
                return 'translated: ' . $label;
            });

        $expectedTransition = new FieldSecurityMetadata(
            'transition1|step1|next_step',
            "translated: transition 1 (translated: step 1 \u{2192} )"
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1',
            '',
            [$expectedTransition]
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testGetMetadataWhenTransitionDoesNotHaveStepTo()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps'       => [
                'step1' => ['allowed_transitions' => ['transition1'], 'label' => 'step 1']
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1']
            ]
        ];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->with(self::isType('string'), [], 'workflows')
            ->willReturnCallback(function ($label) {
                return 'translated: ' . $label;
            });

        $expectedTransition = new FieldSecurityMetadata(
            'transition1|step1|',
            "translated: transition 1 (translated: step 1 \u{2192} )"
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1',
            '',
            [$expectedTransition]
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testGetMetadataWhenWorkflowIsDisabled()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'transitions' => [
                'transition1' => ['label' => 'transition 1']
            ]
        ];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(false);

        self::assertEquals(
            [],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testGetMetadataForWorkflowWithoutTransitions()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->with(self::isType('string'), [], 'workflows')
            ->willReturnCallback(function ($label) {
                return 'translated: ' . $label;
            });

        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1',
            '',
            []
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testStartTransitionShouldNotDuplicateAnotherTransition()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps'       => [
                'step1'     => [
                    'allowed_transitions' => ['start_transition'],
                    'label'               => 'step 1',
                    '_is_start'           => true
                ],
                'next_step' => ['label' => 'next step']
            ],
            'transitions' => [
                'start_transition' => ['label' => 'start transition', 'step_to' => 'next_step', 'is_start' => true],
            ]
        ];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label, $parameters, $domain) {
                $result = 'translated: ' . $label;
                if (!empty($parameters)) {
                    foreach ($parameters as $key => $val) {
                        $result .= ' ' . $key . ': (' . $val . ')';
                    }
                }
                if (!empty($domain)) {
                    $result .= ' [domain: ' . $domain . ']';
                }

                return $result;
            });

        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1 [domain: workflows]',
            '',
            [
                new FieldSecurityMetadata(
                    'start_transition||next_step',
                    'translated: start transition [domain: workflows] '
                    . "(translated: step 1 [domain: workflows] \u{2192} translated: next step [domain: workflows])"
                )
            ]
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
        // test that the local cache is used
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testSortingOfTransitions()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps'       => [
                'step1'     => ['allowed_transitions' => ['transition1'], 'label' => 'step 1', 'order' => 20],
                'step2'     => ['allowed_transitions' => ['transition1'], 'label' => 'step 2', 'order' => 10],
                'next_step' => ['allowed_transitions' => ['transition1'], 'label' => 'next step']
            ],
            'transitions' => [
                'start_transition' => ['label' => 'start transition', 'step_to' => 'next_step', 'is_start' => true],
                'transition1'      => ['label' => 'transition 1', 'step_to' => 'next_step']
            ]
        ];

        $query = $this->loadWorkflowExpectations();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn(
                [
                    [
                        'name'          => $workflowName,
                        'label'         => 'workflow 1',
                        'configuration' => $workflowConfiguration
                    ]
                ]
            );
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label, $parameters, $domain) {
                $result = 'translated: ' . $label;
                if (!empty($parameters)) {
                    foreach ($parameters as $key => $val) {
                        $result .= ' ' . $key . ': (' . $val . ')';
                    }
                }
                if (!empty($domain)) {
                    $result .= ' [domain: ' . $domain . ']';
                }

                return $result;
            });

        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1 [domain: workflows]',
            '',
            [
                new FieldSecurityMetadata(
                    'start_transition||next_step',
                    'translated: start transition [domain: workflows] '
                    . "(translated: (Start) [domain: jsmessages] \u{2192} translated: next step [domain: workflows])"
                ),
                new FieldSecurityMetadata(
                    'transition1|next_step|next_step',
                    'translated: transition 1 [domain: workflows] '
                    . "(translated: next step [domain: workflows] \u{2192} translated: next step [domain: workflows])"
                ),
                new FieldSecurityMetadata(
                    'transition1|step2|next_step',
                    'translated: transition 1 [domain: workflows] '
                    . "(translated: step 2 [domain: workflows] \u{2192} translated: next step [domain: workflows])"
                ),
                new FieldSecurityMetadata(
                    'transition1|step1|next_step',
                    'translated: transition 1 [domain: workflows] '
                    . "(translated: step 1 [domain: workflows] \u{2192} translated: next step [domain: workflows])"
                ),
            ]
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
        // test that the local cache is used
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }
}
