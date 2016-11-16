<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Acl;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity as TestEntity;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * @dbIsolation
 */
class LimitedAccessTest extends WebTestCase
{
    /** @var TestEntity */
    protected $testEntity;

    protected function setUp()
    {
        $userName = 'test_user';
        $userEmail = 'test_user@example.com';
        $userPwd = 'testUserPwd123';

        $this->initClient([], $this->generateBasicAuthHeader($userName, $userPwd));

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroUserBundle:User');

        $organization = $em->getRepository('OroOrganizationBundle:Organization')->find(self::AUTH_ORGANIZATION);
        /** @var User $user */
        $user = $em->getRepository('OroUserBundle:User')->findOneBy(['email' => $userEmail]);
        if (null === $user) {
            /** @var UserManager $userManager */
            $userManager = $this->getContainer()->get('oro_user.manager');

            $user = $userManager->createUser();
            $role = $em->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_USER']);
            $user
                ->setUsername($userName)
                ->setEmail($userEmail)
                ->setPlainPassword($userPwd)
                ->addRole($role)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setFirstName('Test')
                ->setLastName('User')
                ->setSalt('');
            $userManager->updateUser($user);
        }

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
                ->setOwner($em->getRepository('OroUserBundle:User')->findOneBy(['email' => self::AUTH_USER]));
            $em->persist($this->testEntity);
            $em->flush();

            /** @var AclManager $aclManager */
            $aclManager = $this->getContainer()->get('oro_security.acl.manager');
            $fieldEditMaskBuilder = $aclManager
                ->getExtensionSelector()
                ->selectByExtensionKey('entity')
                ->getFieldExtension()
                ->getMaskBuilder('EDIT');
            $fieldEditMaskBuilder
                ->add($fieldEditMaskBuilder->getMask('MASK_VIEW_SYSTEM'))
                ->add($fieldEditMaskBuilder->getMask('MASK_EDIT_BASIC'));
            $aclManager->setFieldPermission(
                $aclManager->getSid($em->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_USER'])),
                $aclManager->getOid('entity:' . TestEntity::class),
                'message',
                $fieldEditMaskBuilder->get()
            );
            $aclManager->flush();
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
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('EXECUTE;action:test_action')
        );
    }

    public function testActionByObjectIdentityDescriptor()
    {
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('EXECUTE', 'action:test_action')
        );
    }


    public function testActionByAclAnnotation()
    {
        $aclAnnotation = new Acl(['id' => 'test_action', 'type' => 'action']);
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('EXECUTE', $aclAnnotation)
        );
    }

    public function testActionByAclAnnotationId()
    {
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('test_action')
        );
    }

    public function testEntityByDescriptor()
    {
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('DELETE;entity:' . TestEntity::class)
        );
    }

    public function testEntityByObjectIdentityDescriptor()
    {
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('DELETE', 'entity:' . TestEntity::class)
        );
    }

    public function testEntityWhenAccessGranted()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('VIEW', 'entity:' . TestEntity::class)
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
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('DELETE', $aclAnnotation)
        );
    }

    public function testEntityByAclAnnotationId()
    {
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('test_entity_delete')
        );
    }

    public function testEntityRecord()
    {
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('DELETE', $this->testEntity)
        );
    }

    public function testEntityRecordWhenAccessGranted()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('VIEW', $this->testEntity)
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
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('DELETE', $objectReference)
        );
    }

    public function testEntityRecordByDomainObjectReferenceWhenAccessGranted()
    {
        $objectReference = new DomainObjectReference(
            TestEntity::class,
            $this->testEntity->getId(),
            $this->testEntity->getOwner()->getId(),
            $this->testEntity->getOrganization()->getId()
        );
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('VIEW', $objectReference)
        );
    }


    public function testEntityRecordByDomainObjectWrapper()
    {
        $objectWrapper = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('DELETE', $objectWrapper)
        );
    }


    public function testEntityRecordByDomainObjectWrapperWhenAccessGranted()
    {
        $objectWrapper = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('VIEW', $objectWrapper)
        );
    }

    public function testEntityField()
    {
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('EDIT', new FieldVote($this->testEntity, 'message'))
        );
    }

    public function testEntityFieldWhenAccessGranted()
    {
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('VIEW', new FieldVote($this->testEntity, 'message'))
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
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('EDIT', new FieldVote($objectReference, 'message'))
        );
    }

    public function testEntityFieldByDomainObjectReferenceWhenAccessGranted()
    {
        $objectReference = new DomainObjectReference(
            TestEntity::class,
            $this->testEntity->getId(),
            $this->testEntity->getOwner()->getId(),
            $this->testEntity->getOrganization()->getId()
        );
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('VIEW', new FieldVote($objectReference, 'message'))
        );
    }

    public function testEntityFieldByDomainObjectWrapper()
    {
        $objectReference = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertFalse(
            $this->getSecurityFacade()->isGranted('EDIT', new FieldVote($objectReference, 'message'))
        );
    }

    public function testEntityFieldByDomainObjectWrapperWhenAccessGranted()
    {
        $objectReference = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertTrue(
            $this->getSecurityFacade()->isGranted('VIEW', new FieldVote($objectReference, 'message'))
        );
    }
}
