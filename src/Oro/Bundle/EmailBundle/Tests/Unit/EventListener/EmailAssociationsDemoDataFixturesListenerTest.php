<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\EventListener\EmailAssociationsDemoDataFixturesListener;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class EmailAssociationsDemoDataFixturesListenerTest extends OrmTestCase
{
    private const LISTENERS = [
        'test_listener_1',
        'test_listener_2',
    ];

    /** @var EntityManagerInterface */
    private $em;

    /** @var OptionalListenerManager|\PHPUnit\Framework\MockObject\MockObject */
    private $listenerManager;

    /** @var AssociationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $associationManager;

    /** @var EmailAddressVisibilityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressVisibilityManager;

    /** @var EmailAssociationsDemoDataFixturesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->associationManager = $this->createMock(AssociationManager::class);
        $this->emailAddressVisibilityManager = $this->createMock(EmailAddressVisibilityManager::class);

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(
            new AnnotationReader(),
            dirname((new \ReflectionClass(Email::class))->getFileName())
        ));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->listener = new EmailAssociationsDemoDataFixturesListener(
            $this->listenerManager,
            $this->associationManager,
            $doctrine,
            $this->emailAddressVisibilityManager
        );
        $this->listener->disableListener(self::LISTENERS[0]);
        $this->listener->disableListener(self::LISTENERS[1]);
    }

    public function testOnPreLoadForNotDemoFixtures(): void
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('disableListeners');

        $this->listener->onPreLoad($event);
    }

    public function testOnPreLoadForDemoFixtures(): void
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $this->listenerManager->expects(self::once())
            ->method('disableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPreLoad($event);
    }

    public function testOnPostLoadForNotDemoFixtures(): void
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $event->expects(self::never())
            ->method('log');
        $this->listenerManager->expects(self::never())
            ->method('enableListeners');

        $this->listener->onPostLoad($event);
    }

    public function testOnPostLoadForDemoFixtures(): void
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $event->expects(self::exactly(3))
            ->method('log')
            ->withConsecutive(
                ['updating email owners'],
                ['updating email address visibilities'],
                ['updating email visibilities']
            );
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->associationManager->expects(self::once())
            ->method('processUpdateAllEmailOwners');

        $this->addQueryExpectation(
            'SELECT o0_.id AS id_0 FROM oro_organization o0_',
            [['id_0' => 1], ['id_0' => 2]]
        );
        $this->addQueryExpectation(
            self::logicalAnd(
                self::stringStartsWith('SELECT o0_.id AS id_0'),
                self::stringEndsWith(
                    'FROM oro_email_user o0_'
                    . ' INNER JOIN oro_organization o1_ ON o0_.organization_id = o1_.id'
                    . ' INNER JOIN oro_email o2_ ON o0_.email_id = o2_.id'
                    . ' LEFT JOIN EmailAddress e3_ ON o2_.from_email_address_id = e3_.id'
                    . ' LEFT JOIN oro_email_recipient o4_ ON o2_.id = o4_.email_id'
                    . ' LEFT JOIN EmailAddress e5_ ON o4_.email_address_id = e5_.id'
                    . ' ORDER BY o2_.id ASC'
                )
            ),
            [['id_0' => 10], ['id_0' => 20]]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->emailAddressVisibilityManager->expects(self::exactly(2))
            ->method('updateEmailAddressVisibilities')
            ->withConsecutive(
                [1],
                [2]
            );

        $this->emailAddressVisibilityManager->expects(self::exactly(2))
            ->method('processEmailUserVisibility')
            ->withConsecutive(
                [self::isInstanceOf(EmailUser::class)],
                [self::isInstanceOf(EmailUser::class)]
            );

        $this->listener->onPostLoad($event);
    }
}
