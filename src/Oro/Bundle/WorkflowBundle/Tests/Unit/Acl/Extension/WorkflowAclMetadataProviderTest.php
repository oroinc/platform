<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Oro\Bundle\WorkflowBundle\Acl\Extension\TransitionLabel;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclMetadata;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclMetadataProvider;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowLabel;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowAclMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var WorkflowAclMetadataProvider */
    private $workflowAclMetadataProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->workflowAclMetadataProvider = new WorkflowAclMetadataProvider(
            $this->doctrine,
            $this->featureChecker
        );
    }

    private function assertGetWorkflowDefinitionsConfigsCall(array $result)
    {
        $em = $this->createMock(EntityManager::class);
        $repo = $this->createMock(WorkflowDefinitionRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(WorkflowDefinition::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getWorkflowDefinitionsConfigs')
            ->willReturn(new \ArrayIterator($result));
    }

    public function testGetMetadata()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps' => [
                'step1' => ['allowed_transitions' => ['transition1', 'transition2'], 'label' => 'step 1'],
                'next_step' => ['label' => 'next step'],
                'final_step' => ['label' => 'final step'],
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1', 'step_to' => 'next_step'],
                'transition2' => [
                    'label' => 'transition 2',
                    'step_to' => 'next_step',
                    'conditional_steps_to' => ['final_step' => []]
                ],
            ]
        ];

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedTransition1 = new FieldSecurityMetadata(
            'transition1|step1|next_step',
            new TransitionLabel('transition 1', 'next step', 'step 1')
        );
        $expectedTransition21 = new FieldSecurityMetadata(
            'transition2|step1|final_step',
            new TransitionLabel('transition 2', 'final step', 'step 1')
        );
        $expectedTransition22 = new FieldSecurityMetadata(
            'transition2|step1|next_step',
            new TransitionLabel('transition 2', 'next step', 'step 1')
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
            [$expectedTransition1, $expectedTransition21, $expectedTransition22]
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
            'steps' => [
                'step1' => ['label' => 'step 1'],
                'next_step' => ['label' => 'next step']
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1', 'step_to' => 'next_step']
            ]
        ];

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
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
            'steps' => [
                'step1' => ['label' => 'step 1'],
                'next_step' => ['label' => 'next step']
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1', 'step_to' => 'next_step', 'is_start' => true]
            ]
        ];

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedTransition = new FieldSecurityMetadata(
            'transition1||next_step',
            new TransitionLabel('transition 1', 'next step')
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
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
            'steps' => [
                'next_step' => ['label' => 'next step']
            ],
            'transitions' => [
                '__start__' => ['label' => 'transition 1', 'step_to' => 'next_step']
            ]
        ];

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedTransition = new FieldSecurityMetadata(
            '__start__||next_step',
            new TransitionLabel('transition 1', 'next step')
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
            [$expectedTransition]
        );
        self::assertEquals(
            [$expectedMetadata],
            $this->workflowAclMetadataProvider->getMetadata()
        );
    }

    public function testGetMetadataWhenTransitionDoesNotExist()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
            'steps' => [
                'step1' => ['allowed_transitions' => ['transition1'], 'label' => 'step 1']
            ],
            'transitions' => []
        ];

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
            []
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
            'steps' => [
                'step1' => ['allowed_transitions' => ['transition1'], 'label' => 'step 1']
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1', 'step_to' => 'next_step']
            ]
        ];

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedTransition = new FieldSecurityMetadata(
            'transition1|step1|next_step',
            new TransitionLabel('transition 1', null, 'step 1')
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
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
            'steps' => [
                'step1' => ['allowed_transitions' => ['transition1'], 'label' => 'step 1']
            ],
            'transitions' => [
                'transition1' => ['label' => 'transition 1']
            ]
        ];

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedTransition = new FieldSecurityMetadata(
            'transition1|step1|',
            new TransitionLabel('transition 1', null, 'step 1')
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
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

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
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

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
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
            'steps' => [
                'step1' => [
                    'allowed_transitions' => ['start_transition'],
                    'label' => 'step 1',
                    '_is_start' => true
                ],
                'next_step' => ['label' => 'next step']
            ],
            'transitions' => [
                'start_transition' => ['label' => 'start transition', 'step_to' => 'next_step', 'is_start' => true],
            ]
        ];

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
            [
                new FieldSecurityMetadata(
                    'start_transition||next_step',
                    new TransitionLabel('start transition', 'next step', 'step 1')
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
            'steps' => [
                'step1' => ['allowed_transitions' => ['transition1'], 'label' => 'step 1', 'order' => 20],
                'step2' => ['allowed_transitions' => ['transition1'], 'label' => 'step 2', 'order' => 10],
                'next_step' => ['allowed_transitions' => ['transition1'], 'label' => 'next step']
            ],
            'transitions' => [
                'start_transition' => ['label' => 'start transition', 'step_to' => 'next_step', 'is_start' => true],
                'transition1' => ['label' => 'transition 1', 'step_to' => 'next_step']
            ]
        ];

        $this->assertGetWorkflowDefinitionsConfigsCall([
            [
                'name' => $workflowName,
                'label' => 'workflow 1',
                'configuration' => $workflowConfiguration
            ]
        ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($workflowName)
            ->willReturn(true);

        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            new WorkflowLabel('workflow 1'),
            null,
            [
                new FieldSecurityMetadata(
                    'start_transition||next_step',
                    new TransitionLabel('start transition', 'next step')
                ),
                new FieldSecurityMetadata(
                    'transition1|next_step|next_step',
                    new TransitionLabel('transition 1', 'next step', 'next step')
                ),
                new FieldSecurityMetadata(
                    'transition1|step2|next_step',
                    new TransitionLabel('transition 1', 'next step', 'step 2')
                ),
                new FieldSecurityMetadata(
                    'transition1|step1|next_step',
                    new TransitionLabel('transition 1', 'next step', 'step 1')
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
}
