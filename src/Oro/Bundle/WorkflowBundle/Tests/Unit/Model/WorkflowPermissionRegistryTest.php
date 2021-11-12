<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Testing\Unit\EntityTrait;

class WorkflowPermissionRegistryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var WorkflowEntityAclRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $aclRepository;

    /** @var WorkflowEntityAclIdentityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $aclIdentityRepository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var WorkflowPermissionRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->aclRepository = $this->createMock(WorkflowEntityAclRepository::class);
        $this->aclIdentityRepository = $this->createMock(WorkflowEntityAclIdentityRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with()
            ->willReturnMap([
                ['OroWorkflowBundle:WorkflowEntityAcl', $this->aclRepository],
                ['OroWorkflowBundle:WorkflowEntityAclIdentity', $this->aclIdentityRepository],
            ]);

        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);

        $this->registry = new WorkflowPermissionRegistry($this->doctrineHelper, $this->workflowRegistry);
    }

    public function testGetPermissionByClassAndIdentifier(): void
    {
        $acl = $this->getEntity(
            WorkflowEntityAcl::class,
            [
                'id' => 1001,
                'entityClass' => \stdClass::class,
                'definition' => $this->getEntity(
                    WorkflowDefinition::class,
                    [
                        'relatedEntity' => WorkflowAwareEntity::class
                    ]
                ),
                'updatable' => false,
                'deletable' => false,
            ]
        );

        $this->aclRepository->expects($this->once())
            ->method('getWorkflowEntityAcls')
            ->willReturn([$acl]);

        $this->aclIdentityRepository->expects($this->once())
            ->method('findByClassAndIdentifierAndActiveWorkflows')
            ->with(\stdClass::class, 42)
            ->willReturn([$this->getEntity(WorkflowEntityAclIdentity::class, ['acl' => $acl])]);

        $this->assertEquals(
            [
                'UPDATE' => false,
                'DELETE' => false,
            ],
            $this->registry->getPermissionByClassAndIdentifier(\stdClass::class, 42)
        );
    }
}
