<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Acl;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity as TestEntity;

/**
 * @dbIsolation
 */
class FullAccessTest extends WebTestCase
{
    /** @var TestEntity */
    protected $testEntity;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroUserBundle:User');

        $user = $em->getRepository('OroUserBundle:User')->findOneBy(['email' => self::AUTH_USER]);
        $organization = $em->getRepository('OroOrganizationBundle:Organization')->find(self::AUTH_ORGANIZATION);

        $token = new UsernamePasswordOrganizationToken($user, $user->getUsername(), 'main', $organization);
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $this->testEntity = $em->getRepository(TestEntity::class)
            ->createQueryBuilder('e')
            ->orderBy('e.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
        if (null === $this->testEntity) {
            $this->testEntity = new TestEntity();
            $this->testEntity
                ->setMessage('test')
                ->setOrganization($organization)
                ->setOwner($user);
            $em->persist($this->testEntity);
            $em->flush();
        }
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->getContainer()->get('oro_security.security_facade');
    }

    public function testActionByDescriptor()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('EXECUTE;action:test_action')
        );
    }

    public function testActionByObjectIdentityDescriptor()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('EXECUTE', 'action:test_action')
        );
    }

    public function testActionByAclAnnotation()
    {
        $aclAnnotation = new Acl(['id' => 'test_action', 'type' => 'action']);
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('EXECUTE', $aclAnnotation)
        );
    }

    public function testActionByAclAnnotationId()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('test_action')
        );
    }

    public function testEntityByDescriptor()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('DELETE;entity:' . TestEntity::class)
        );
    }

    public function testEntityByObjectIdentityDescriptor()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('DELETE', 'entity:' . TestEntity::class)
        );
    }

    public function testEntityByAclAnnotation()
    {
        $aclAnnotation = new Acl([
            'id'         => 'test_entity_delete',
            'type'       => 'entity',
            'permission' => 'DELETE',
            'class'      => TestEntity::class
        ]);
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('DELETE', $aclAnnotation)
        );
    }

    public function testEntityByAclAnnotationId()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('test_entity_delete')
        );
    }

    public function testEntityRecord()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('DELETE', $this->testEntity)
        );
    }

    public function testEntityRecordByDomainObjectReference()
    {
        $objectReference = new DomainObjectReference(
            TestEntity::class,
            $this->testEntity->getId(),
            $this->testEntity->getOwner()->getId(),
            $this->testEntity->getOrganization()->getId()
        );
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('DELETE', $objectReference)
        );
    }

    public function testEntityRecordByDomainObjectWrapper()
    {
        $objectWrapper = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('DELETE', $objectWrapper)
        );
    }

    public function testEntityField()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('EDIT', new FieldVote($this->testEntity, 'message'))
        );
    }

    public function testEntityFieldByDomainObjectReference()
    {
        $objectReference = new DomainObjectReference(
            TestEntity::class,
            $this->testEntity->getId(),
            $this->testEntity->getOwner()->getId(),
            $this->testEntity->getOrganization()->getId()
        );
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('EDIT', new FieldVote($objectReference, 'message'))
        );
    }

    public function testEntityFieldByDomainObjectWrapper()
    {
        $objectReference = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('EDIT', new FieldVote($objectReference, 'message'))
        );
    }
}
