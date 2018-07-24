<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\Voter\WorkflowEntityVoter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter\Stub\WorkflowEntity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class WorkflowEntityVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WorkflowEntityVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WorkflowRegistry
     */
    protected $workflowRegistry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WorkflowPermissionRegistry
     */
    protected $permissionRegistry;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();

        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->permissionRegistry = new WorkflowPermissionRegistry($this->doctrineHelper, $this->workflowRegistry);

        $this->voter = new WorkflowEntityVoter($this->doctrineHelper, $this->permissionRegistry);
    }

    protected function tearDown()
    {
        unset($this->voter, $this->doctrineHelper);
    }

    /**
     * @param int $expected
     * @param object $object
     * @param array $attributes
     * @param bool $updatable
     * @param bool $deletable
     * @dataProvider voteDataProvider
     */
    public function testVote($expected, $object, array $attributes = [], $updatable = true, $deletable = true)
    {
        $definition = new WorkflowDefinition();
        $definition->setRelatedEntity('SupportedClass');

        $entityAcl = new WorkflowEntityAcl();
        $entityAcl->setEntityClass('WorkflowEntity')
            ->setUpdatable($updatable)
            ->setDeletable($deletable)
            ->setDefinition($definition);

        $aclIdentity = new WorkflowEntityAclIdentity();
        $aclIdentity->setAcl($entityAcl);

        $identifier = null;
        if ($object instanceof WorkflowEntity) {
            $identifier = $object->getId();
            $this->setDoctrineHelper('WorkflowEntity', $identifier);
        } elseif ($object instanceof ObjectIdentity && filter_var($object->getIdentifier(), FILTER_VALIDATE_INT)) {
            $identifier = $object->getIdentifier();
        }

        $this->setRegistryRepositories([$entityAcl], 'WorkflowEntity', $identifier, [$aclIdentity]);

        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertEquals($expected, $this->voter->vote($token, $object, $attributes));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function voteDataProvider()
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
                'object' => new ObjectIdentity('entity', 'WorkflowEntity'),
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
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => ['DELETE'],
            ],
            'update denied' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
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
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
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
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
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
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
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
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => ['DELETE', 'CREATE'],
                'updatable' => true,
                'deletable' => false
            ],
        ];
    }

    /**
     * @param array $entityAcls
     * @param string|null $entityClass
     * @param int|null $entityIdentifier
     * @param array $aclIdentities
     */
    protected function setRegistryRepositories(
        array $entityAcls = [],
        $entityClass = null,
        $entityIdentifier = null,
        array $aclIdentities = []
    ) {
        $entityAclRepository =
            $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->setMethods(['getWorkflowEntityAcls'])
                ->getMock();
        $entityAclRepository->expects($this->any())
            ->method('getWorkflowEntityAcls')
            ->will($this->returnValue($entityAcls));

        $aclIdentityRepository =
            $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository')
                ->disableOriginalConstructor()
                ->getMock();
        if ($entityClass && $entityIdentifier) {
            $aclIdentityRepository->expects($this->any())
                ->method('findByClassAndIdentifier')
                ->with($entityClass, $entityIdentifier)
                ->will($this->returnValue($aclIdentities));
        }

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with($this->isType('string'))
            ->will(
                $this->returnCallback(
                    function ($entity) use ($entityAclRepository, $aclIdentityRepository) {
                        switch ($entity) {
                            case 'OroWorkflowBundle:WorkflowEntityAcl':
                                return $entityAclRepository;
                            case 'OroWorkflowBundle:WorkflowEntityAclIdentity':
                                return $aclIdentityRepository;
                            default:
                                return null;
                        }
                    }
                )
            );

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();

        $this->workflowRegistry->expects($this->any())
            ->method('getActiveWorkflowsByEntityClass')
            ->with('SupportedClass')
            ->willReturn(new ArrayCollection([$workflow]));
    }

    /**
     * @param string $entityClass
     * @param int|null $entityIdentifier
     */
    protected function setDoctrineHelper($entityClass, $entityIdentifier)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($this->isType('object'))
            ->will($this->returnValue($entityClass));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->isType('object'), false)
            ->will($this->returnValue($entityIdentifier));
    }
}
