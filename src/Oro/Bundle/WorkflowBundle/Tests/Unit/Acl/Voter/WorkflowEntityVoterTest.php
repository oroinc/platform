<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\WorkflowBundle\Acl\Voter\WorkflowEntityVoter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter\Stub\WorkflowEntity;

class WorkflowEntityVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowEntityVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->voter = new WorkflowEntityVoter($this->registry, $this->doctrineHelper, $this->workflowManager);
    }

    protected function tearDown()
    {
        unset($this->voter);
        unset($this->registry);
        unset($this->doctrineHelper);
        unset($this->workflowManager);
    }

    /**
     * @param string $attribute
     * @param bool $expected
     * @dataProvider supportsAttributeDataProvider
     */
    public function testSupportsAttribute($attribute, $expected)
    {
        $this->assertEquals($expected, $this->voter->supportsAttribute($attribute));
    }

    public function supportsAttributeDataProvider()
    {
        return array(
            'VIEW'   => array('VIEW', false),
            'CREATE' => array('CREATE', false),
            'EDIT'   => array('EDIT', true),
            'DELETE' => array('DELETE', true),
            'ASSIGN' => array('ASSIGN', false),
        );
    }

    /**
     * @param string $class
     * @param bool $expected
     * @dataProvider supportsClassDataProvider
     */
    public function testSupportsClass($class, $expected)
    {
        $entityAcl = new WorkflowEntityAcl();
        $entityAcl->setEntityClass('SupportedClass');
        $this->setRegistryRepositories(array($entityAcl));

        $this->assertEquals($expected, $this->voter->supportsClass($class));
    }

    public function supportsClassDataProvider()
    {
        return array(
            'supported class'     => array('SupportedClass', true),
            'not supported class' => array('NotSupportedClass', false),
        );
    }

    /**
     * @param int $expected
     * @param object $object
     * @param array $attributes
     * @param bool $hasWorkflow
     * @param bool $updatable
     * @param bool $deletable
     * @dataProvider voteDataProvider
     */
    public function testVote(
        $expected,
        $object,
        array $attributes = array(),
        $hasWorkflow = false,
        $updatable = true,
        $deletable = true
    ) {
        $entityAcl = new WorkflowEntityAcl();
        $entityAcl->setEntityClass('WorkflowEntity')
            ->setUpdatable($updatable)
            ->setDeletable($deletable);

        $aclIdentity = new WorkflowEntityAclIdentity();
        $aclIdentity->setAcl($entityAcl);

        $identifier = null;
        $class = null;
        if ($object instanceof WorkflowEntity) {
            $identifier = $object->getId();
            $this->setDoctrineHelper('WorkflowEntity', $identifier);
            $class = 'WorkflowEntity';
        } elseif ($object instanceof ObjectIdentity && filter_var($object->getIdentifier(), FILTER_VALIDATE_INT)) {
            $identifier = $object->getIdentifier();
            $class = $object->getType();
        }

        $this->setRegistryRepositories(array($entityAcl), $class, $identifier, array($aclIdentity));
        $this->setWorkflowManager($class, $hasWorkflow);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertEquals($expected, $this->voter->vote($token, $object, $attributes));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function voteDataProvider()
    {
        return array(
            'empty object' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => null,
            ),
            'not an object' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => 'not an object',
            ),
            'not supported object identity' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new ObjectIdentity('entity', 'WorkflowEntity'),
            ),
            'not persisted object' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(),
            ),
            'no applicable workflow' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(1),
                'attributes' => array('EDIT'),
            ),
            'not supported attributes' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(1),
                'attributes' => array('VIEW', 'ASSIGN'),
                'hasWorkflow' => true,
            ),
            'no attributes' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(1),
                'attributes' => array(),
                'hasWorkflow' => true,
            ),
            'not supported class' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new ObjectIdentity('1', 'UnknownEntity'),
                'attributes' => array('EDIT'),
                'hasWorkflow' => true,
            ),
            'update granted' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('EDIT'),
                'hasWorkflow' => true,
            ),
            'delete granted' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('DELETE'),
                'hasWorkflow' => true,
            ),
            'update denied' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('EDIT'),
                'hasWorkflow' => true,
                'updatable' => false,
            ),
            'delete denied' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('DELETE'),
                'hasWorkflow' => true,
                'updatable' => true,
                'deletable' => false,
            ),
            'update granted and delete granted' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('EDIT', 'DELETE'),
                'hasWorkflow' => true,
            ),
            'update denied and delete granted' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('EDIT', 'DELETE'),
                'hasWorkflow' => true,
                'updatable' => false,
            ),
            'update granted and delete denied' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('EDIT', 'DELETE'),
                'hasWorkflow' => true,
                'updatable' => true,
                'deletable' => false,
            ),
            'update denied and delete denied' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('EDIT', 'DELETE'),
                'hasWorkflow' => true,
                'updatable' => false,
                'deletable' => false,
            ),
            'update granted with not supported attribute' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('EDIT', 'VIEW'),
                'hasWorkflow' => true,
            ),
            'update denied with not supported attribute' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('EDIT', 'ASSIGN'),
                'hasWorkflow' => true,
                'updatable' => false,
            ),
            'delete granted with not supported attribute' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('DELETE', 'VIEW'),
                'hasWorkflow' => true,
            ),
            'delete denied with not supported attribute' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('DELETE', 'CREATE'),
                'hasWorkflow' => true,
                'updatable' => true,
                'deletable' => false
            ),
        );
    }

    /**
     * @param array $entityAcls
     * @param string|null $entityClass
     * @param int|null $entityIdentifier
     * @param array $aclIdentities
     */
    protected function setRegistryRepositories(
        array $entityAcls = array(),
        $entityClass = null,
        $entityIdentifier = null,
        array $aclIdentities = array()
    ) {
        $entityAclRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityAclRepository->expects($this->any())
            ->method('findAll')
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

        $this->registry->expects($this->any())
            ->method('getRepository')
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

    /**
     * @param string $entityClass
     * @param bool $hasWorkflow
     */
    protected function setWorkflowManager($entityClass, $hasWorkflow)
    {
        $this->workflowManager->expects($this->any())
            ->method('hasApplicableWorkflowByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue($hasWorkflow));
    }
}
