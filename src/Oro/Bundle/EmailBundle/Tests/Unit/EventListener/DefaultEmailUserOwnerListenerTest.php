<?php

namespace Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\EventListener\DefaultEmailUserOwnerListener;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultEmailUserOwnerListenerTest extends TestCase
{
    private DefaultEmailUserOwnerListener $listener;
    private DefaultUserProvider|MockObject $defaultUserProvider;

    protected function setUp(): void
    {
        $this->defaultUserProvider = self::createMock(DefaultUserProvider::class);
        $this->listener = new DefaultEmailUserOwnerListener($this->defaultUserProvider);
    }

    public function testPrePersistWithInvalidEntity(): void
    {
        $entity = new TestActivity();
        $event = self::getEvent($entity);

        $this->listener->prePersist($event);

        self::assertNull($entity->getOwner());
        self::assertNull($entity->getOrganization());
    }

    public function testPrePersistWithValidEntity(): void
    {
        $owner = self::getUser();
        self::assertDefaultOwner($owner);

        $entity = new EmailUser();
        $event = self::getEvent($entity);

        $this->listener->prePersist($event);

        self::assertSame($owner, $entity->getOwner());
        self::assertSame($owner->getOrganization(), $entity->getOrganization());
    }

    public function testPrePersistWithEntityAndOwner(): void
    {
        $defaultOwner = self::getUser();
        $owner = self::getUser();
        self::assertDefaultOwner($defaultOwner);

        $entity = new EmailUser();
        $entity->setOwner($owner);
        $entity->setOrganization($owner->getOrganization());

        $event = self::getEvent($entity);

        $this->listener->prePersist($event);

        self::assertSame($owner, $entity->getOwner());
        self::assertSame($owner->getOrganization(), $entity->getOrganization());
    }

    private function assertDefaultOwner(User $user): void
    {
        $this->defaultUserProvider
            ->expects(self::any())
            ->method('getDefaultUser')
            ->willReturn($user);
    }

    private function getEvent(object $entity): PrePersistEventArgs
    {
        $objectManager = self::createMock(EntityManagerInterface::class);

        return new PrePersistEventArgs($entity, $objectManager);
    }

    private function getUser(): User
    {
        $organization = new Organization();
        $user = new User();
        $user->setOrganization($organization);

        return $user;
    }
}
