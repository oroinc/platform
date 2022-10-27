<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Acl\Voter\WorkflowEntityVoter;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter\Stub\WorkflowEntity;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class WorkflowEntityVoterTest extends \PHPUnit\Framework\TestCase
{
    private const SUPPORTED_CLASS = User::class;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var WorkflowEntityVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);

        $container = TestContainerBuilder::create()
            ->add(
                'oro_workflow.permission_registry',
                new WorkflowPermissionRegistry($this->doctrineHelper, $this->workflowRegistry)
            )
            ->getContainer($this);

        $this->voter = new WorkflowEntityVoter($this->doctrineHelper, $container);
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(
        int $expected,
        object|string|null $object,
        array $attributes = [],
        bool $updatable = true,
        bool $deletable = true
    ) {
        $definition = new WorkflowDefinition();
        $definition->setRelatedEntity(self::SUPPORTED_CLASS);

        $entityAcl = new WorkflowEntityAcl();
        $entityAcl->setEntityClass(WorkflowEntity::class)
            ->setUpdatable($updatable)
            ->setDeletable($deletable)
            ->setDefinition($definition);

        $aclIdentity = new WorkflowEntityAclIdentity();
        $aclIdentity->setAcl($entityAcl);

        $identifier = null;
        if ($object instanceof WorkflowEntity) {
            $identifier = $object->getId();
            $this->doctrineHelper->expects($this->any())
                ->method('getSingleEntityIdentifier')
                ->with($this->isType('object'), false)
                ->willReturn($identifier);
        } elseif ($object instanceof ObjectIdentity && filter_var($object->getIdentifier(), FILTER_VALIDATE_INT)) {
            $identifier = $object->getIdentifier();
        }

        $this->setRegistryRepositories([$entityAcl], WorkflowEntity::class, $identifier, [$aclIdentity]);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals($expected, $this->voter->vote($token, $object, $attributes));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function voteDataProvider(): array
    {
        return [
            'empty object' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => null,
            ],
            'not an object' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => 'not an object',
            ],
            'not supported object identity' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new ObjectIdentity('entity', WorkflowEntity::class),
            ],
            'not persisted object' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(),
            ],
            'not supported attributes' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(1),
                'attributes' => ['VIEW', 'ASSIGN'],
            ],
            'no attributes' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(1),
                'attributes' => [],
            ],
            'not supported class' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new ObjectIdentity('1', 'UnknownEntity'),
                'attributes' => ['EDIT'],
            ],
            'update granted' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(1),
                'attributes' => ['EDIT'],
            ],
            'delete granted' => [
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new ObjectIdentity('1', WorkflowEntity::class),
                'attributes' => ['DELETE'],
            ],
            'update denied' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new ObjectIdentity('1', WorkflowEntity::class),
                'attributes' => ['EDIT'],
                'updatable' => false,
            ],
            'delete denied' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new WorkflowEntity(1),
                'attributes' => ['DELETE'],
                'updatable' => true,
                'deletable' => false,
            ],
            'update granted and delete granted' => [
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new ObjectIdentity('1', WorkflowEntity::class),
                'attributes' => ['EDIT', 'DELETE'],
            ],
            'update denied and delete granted' => [
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new WorkflowEntity(1),
                'attributes' => ['EDIT', 'DELETE'],
                'updatable' => false,
            ],
            'update granted and delete denied' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new WorkflowEntity(1),
                'attributes' => ['EDIT', 'DELETE'],
                'updatable' => true,
                'deletable' => false,
            ],
            'update denied and delete denied' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', WorkflowEntity::class),
                'attributes' => ['DELETE'],
                'updatable' => false,
                'deletable' => false,
            ],
            'update granted with not supported attribute' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(1),
                'attributes' => ['EDIT', 'VIEW'],
            ],
            'update denied with not supported attribute' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new ObjectIdentity('1', WorkflowEntity::class),
                'attributes' => ['EDIT', 'ASSIGN'],
                'updatable' => false,
            ],
            'delete granted with not supported attribute' => [
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new WorkflowEntity(1),
                'attributes' => ['DELETE', 'VIEW'],
            ],
            'delete denied with not supported attribute' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', WorkflowEntity::class),
                'attributes' => ['DELETE', 'CREATE'],
                'updatable' => true,
                'deletable' => false
            ],
        ];
    }

    private function setRegistryRepositories(
        array $entityAcls = [],
        ?string $entityClass = null,
        ?int $entityIdentifier = null,
        array $aclIdentities = []
    ) {
        $entityAclRepository =$this->createMock(WorkflowEntityAclRepository::class);
        $entityAclRepository->expects($this->any())
            ->method('getWorkflowEntityAcls')
            ->willReturn($entityAcls);

        $aclIdentityRepository = $this->createMock(WorkflowEntityAclIdentityRepository::class);
        if ($entityClass && $entityIdentifier) {
            $aclIdentityRepository->expects($this->any())
                ->method('findByClassAndIdentifierAndActiveWorkflows')
                ->with($entityClass, $entityIdentifier)
                ->willReturn($aclIdentities);
        }

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with($this->isType('string'))
            ->willReturnCallback(function ($entity) use ($entityAclRepository, $aclIdentityRepository) {
                return match ($entity) {
                    'OroWorkflowBundle:WorkflowEntityAcl' => $entityAclRepository,
                    'OroWorkflowBundle:WorkflowEntityAclIdentity' => $aclIdentityRepository,
                    default => null
                };
            });

        $workflow = $this->createMock(Workflow::class);

        $this->workflowRegistry->expects($this->any())
            ->method('getActiveWorkflowsByEntityClass')
            ->with(self::SUPPORTED_CLASS)
            ->willReturn(new ArrayCollection([$workflow]));
    }
}
