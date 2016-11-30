<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclMetadata;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclMetadataProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowAclMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $featureChecker;

    /**
     * @var WorkflowAclMetadataProvider
     */
    protected $workflowAclMetadataProvider;

    protected function setUp()
    {
        $this->doctrine = $this->getMock(ManagerRegistry::class);
        $this->translator = $this->getMock(TranslatorInterface::class);
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
     * @return \PHPUnit_Framework_MockObject_MockObject
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
                    $result .= ' domain: ' . $domain;
                }

                return $result;
            });

        $expectedTransition = new FieldSecurityMetadata(
            'transition1',
            'translated: transition 1 domain: workflows',
            [],
            'translated: oro.workflow.transition.description %toStep%: (translated: next step domain: workflows)'
        );
        $expectedMetadata = new WorkflowAclMetadata(
            $workflowName,
            'translated: workflow 1 domain: workflows',
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

    public function testGetMetadataWhenStepDoesNotExist()
    {
        $workflowName = 'workflow1';
        $workflowConfiguration = [
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
            'transition1',
            'translated: transition 1'
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
            'transition1',
            'translated: transition 1'
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
}
