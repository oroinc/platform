<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Acl;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership as TestEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FullAccessTest extends WebTestCase
{
    /** @var TestEntity */
    private $testEntity;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(User::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::AUTH_USER]);
        $organization = $em->getRepository(Organization::class)->find(self::AUTH_ORGANIZATION);

        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->testEntity = $em->getRepository(TestEntity::class)
            ->createQueryBuilder('e')
            ->orderBy('e.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
        if (null === $this->testEntity) {
            $this->testEntity = new TestEntity();
            $this->testEntity
                ->setName('test')
                ->setOrganization($organization)
                ->setOwner($user);
            $em->persist($this->testEntity);
            $em->flush();
        }
    }

    private function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->getContainer()->get('security.authorization_checker');
    }

    public function testActionByDescriptor()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('EXECUTE;action:test_action')
        );
    }

    public function testActionByObjectIdentityDescriptor()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('EXECUTE', 'action:test_action')
        );
    }

    public function testActionByAclAnnotation()
    {
        $aclAnnotation = new Acl(['id' => 'test_action', 'type' => 'action']);
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('EXECUTE', $aclAnnotation)
        );
    }

    public function testActionByAclAnnotationId()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('test_action')
        );
    }

    public function testEntityByDescriptor()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('DELETE;entity:' . TestEntity::class)
        );
    }

    public function testEntityByObjectIdentityDescriptor()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('DELETE', 'entity:' . TestEntity::class)
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
            $this->getAuthorizationChecker()->isGranted('DELETE', $aclAnnotation)
        );
    }

    public function testEntityByAclAnnotationId()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('test_entity_delete')
        );
    }

    public function testEntityRecord()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('DELETE', $this->testEntity)
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
            $this->getAuthorizationChecker()->isGranted('DELETE', $objectReference)
        );
    }

    public function testEntityRecordByDomainObjectWrapper()
    {
        $objectWrapper = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('DELETE', $objectWrapper)
        );
    }

    public function testEntityField()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('EDIT', new FieldVote($this->testEntity, 'name'))
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
            $this->getAuthorizationChecker()->isGranted('EDIT', new FieldVote($objectReference, 'name'))
        );
    }

    public function testEntityFieldByDomainObjectWrapper()
    {
        $objectReference = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('EDIT', new FieldVote($objectReference, 'name'))
        );
    }
}
