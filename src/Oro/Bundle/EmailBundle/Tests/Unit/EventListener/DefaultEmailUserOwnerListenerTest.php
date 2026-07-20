<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\EventListener\DefaultEmailUserOwnerListener;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultEmailUserOwnerListenerTest extends TestCase
{
    private DefaultUserProvider&MockObject $defaultUserProvider;
    private DefaultEmailUserOwnerListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);

        $this->listener = new DefaultEmailUserOwnerListener($this->defaultUserProvider);
    }

    public function testPrePersistWithInvalidEntity(): void
    {
        $entity = new TestActivity();
        $event = $this->getEvent($entity);

        $this->listener->prePersist($event);

        self::assertNull($entity->getOwner());
        self::assertNull($entity->getOrganization());
    }

    public function testPrePersistWithValidEntity(): void
    {
        $owner = $this->getUser();
        $this->assertDefaultOwner($owner);

        $entity = new EmailUser();
        $event = $this->getEvent($entity);

        $this->listener->prePersist($event);

        self::assertSame($owner, $entity->getOwner());
        self::assertSame($owner->getOrganization(), $entity->getOrganization());
    }

    public function testPrePersistWithEntityAndOwner(): void
    {
        $defaultOwner = $this->getUser();
        $owner = $this->getUser();
        $this->assertDefaultOwner($defaultOwner);

        $entity = new EmailUser();
        $entity->setOwner($owner);
        $entity->setOrganization($owner->getOrganization());

        $event = $this->getEvent($entity);

        $this->listener->prePersist($event);

        self::assertSame($owner, $entity->getOwner());
        self::assertSame($owner->getOrganization(), $entity->getOrganization());
    }

    public function testPrePersistWithEntityAndMailboxOwner(): void
    {
        $defaultOwner = $this->getUser();
        $this->assertDefaultOwner($defaultOwner);

        $organization = new Organization();
        $entity = new EmailUser();
        $entity->setMailboxOwner(new Mailbox());
        $entity->setOrganization($organization);

        $event = $this->getEvent($entity);

        $this->listener->prePersist($event);

        self::assertNull($entity->getOwner());
        self::assertSame($organization, $entity->getOrganization());
    }

    public function testPrePersistWithMailboxOwnerButNoOrganization(): void
    {
        $defaultOwner = $this->getUser();
        $this->assertDefaultOwner($defaultOwner);

        $entity = new EmailUser();
        $entity->setMailboxOwner(new Mailbox());

        $event = $this->getEvent($entity);

        $this->listener->prePersist($event);

        self::assertSame($defaultOwner, $entity->getOwner());
        self::assertSame($defaultOwner->getOrganization(), $entity->getOrganization());
    }

    public function testPrePersistWithOwnerButNoOrganization(): void
    {
        $defaultOwner = $this->getUser();
        $owner = $this->getUser();
        $this->assertDefaultOwner($defaultOwner);

        $entity = new EmailUser();
        $entity->setOwner($owner);

        $event = $this->getEvent($entity);

        $this->listener->prePersist($event);

        self::assertSame($defaultOwner, $entity->getOwner());
        self::assertSame($defaultOwner->getOrganization(), $entity->getOrganization());
    }

    private function assertDefaultOwner(User $user): void
    {
        $this->defaultUserProvider->expects(self::any())
            ->method('getDefaultUser')
            ->willReturn($user);
    }

    private function getEvent(object $entity): PrePersistEventArgs
    {
        return new PrePersistEventArgs($entity, $this->createMock(EntityManagerInterface::class));
    }

    private function getUser(): User
    {
        $organization = new Organization();
        $user = new User();
        $user->setOrganization($organization);

        return $user;
    }
}
