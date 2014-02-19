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

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->voter = new WorkflowEntityVoter($this->registry, $this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->voter);
        unset($this->registry);
        unset($this->doctrineHelper);
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
     * @param bool $updatable
     * @param bool $deletable
     * @dataProvider voteDataProvider
     */
    public function testVote($expected, $object, array $attributes = array(), $updatable = true, $deletable = true)
    {
        $entityAcl = new WorkflowEntityAcl();
        $entityAcl->setEntityClass('WorkflowEntity')
            ->setUpdatable($updatable)
            ->setDeletable($deletable);

        $aclIdentity = new WorkflowEntityAclIdentity();
        $aclIdentity->setAcl($entityAcl);

        $identifier = null;
        if ($object instanceof WorkflowEntity) {
            $identifier = $object->getId();
            $this->setDoctrineHelper('WorkflowEntity', $identifier);
        } elseif ($object instanceof ObjectIdentity && filter_var($object->getIdentifier(), FILTER_VALIDATE_INT)) {
            $identifier = $object->getIdentifier();
        }

        $this->setRegistryRepositories(array($entityAcl), 'WorkflowEntity', $identifier, array($aclIdentity));

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
            'not supported attributes' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(1),
                'attributes' => array('VIEW', 'ASSIGN'),
            ),
            'no attributes' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new WorkflowEntity(1),
                'attributes' => array(),
            ),
            'not supported class' => array(
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'object' => new ObjectIdentity('1', 'UnknownEntity'),
                'attributes' => array('EDIT'),
            ),
            'update granted' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('EDIT'),
            ),
            'delete granted' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('DELETE'),
            ),
            'update denied' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('EDIT'),
                'updatable' => false,
            ),
            'delete denied' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('DELETE'),
                'updatable' => true,
                'deletable' => false,
            ),
            'update granted and delete granted' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('EDIT', 'DELETE'),
            ),
            'update denied and delete granted' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('EDIT', 'DELETE'),
                'updatable' => false,
            ),
            'update granted and delete denied' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('EDIT', 'DELETE'),
                'updatable' => true,
                'deletable' => false,
            ),
            'update denied and delete denied' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('EDIT', 'DELETE'),
                'updatable' => false,
                'deletable' => false,
            ),
            'update granted with not supported attribute' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('EDIT', 'VIEW'),
            ),
            'update denied with not supported attribute' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('EDIT', 'ASSIGN'),
                'updatable' => false,
            ),
            'delete granted with not supported attribute' => array(
                'expected' => VoterInterface::ACCESS_GRANTED,
                'object' => new WorkflowEntity(1),
                'attributes' => array('DELETE', 'VIEW'),
            ),
            'delete denied with not supported attribute' => array(
                'expected' => VoterInterface::ACCESS_DENIED,
                'object' => new ObjectIdentity('1', 'WorkflowEntity'),
                'attributes' => array('DELETE', 'CREATE'),
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
}
