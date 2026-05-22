<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Isolator;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Event\OroEntityListenerResolver;
use Oro\Bundle\EntityBundle\Event\OroEventManager;
use Oro\Component\DraftSession\Exception\DraftSessionLogicException;
use Oro\Component\DraftSession\Isolator\DoctrineListenersIsolator;
use Oro\Component\DraftSession\Tests\Unit\Stub\InvalidListenerResolverStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DoctrineListenersIsolatorTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;
    private Configuration&MockObject $configuration;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->configuration = $this->createMock(Configuration::class);
    }

    /**
     * @dataProvider disableListenersWhitelistProvider
     *
     * @param list<string> $whitelist
     */
    public function testDisableListenersWithOroEventManager(array $whitelist, string $expectedPattern): void
    {
        $oroEventManager = $this->createMock(OroEventManager::class);
        $entityListenerResolver = $this->createMock(OroEntityListenerResolver::class);

        $this->doctrine
            ->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getEventManager')
            ->willReturn($oroEventManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->configuration
            ->expects(self::once())
            ->method('getEntityListenerResolver')
            ->willReturn($entityListenerResolver);

        $oroEventManager
            ->expects(self::once())
            ->method('disableListeners')
            ->with($expectedPattern);

        $entityListenerResolver
            ->expects(self::once())
            ->method('disableListeners')
            ->with($expectedPattern);

        $isolator = new DoctrineListenersIsolator($this->doctrine, \stdClass::class, $whitelist);
        $isolator->disableListeners();
    }

    public static function disableListenersWhitelistProvider(): iterable
    {
        yield 'no whitelist' => [[], '.*'];
        yield 'single whitelisted class' => [['Foo\Bar'], '^(?!Foo\\\Bar$).*'];
        yield 'multiple whitelisted classes' => [['Foo\Bar', 'Baz\Qux'], '^(?!Foo\\\Bar$|Baz\\\Qux$).*'];
    }

    public function testDisableListenersThrowsWhenEventManagerIsNotOroEventManager(): void
    {
        $plainEventManager = $this->createMock(EventManager::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getEventManager')
            ->willReturn($plainEventManager);

        $draftSessionEventListenersIsolator =
            new DoctrineListenersIsolator($this->doctrine, \stdClass::class);

        $this->expectException(DraftSessionLogicException::class);
        $this->expectExceptionMessage('Event manager is expected to be an instance of');

        $draftSessionEventListenersIsolator->disableListeners();
    }

    public function testDisableListenersThrowsWhenResolverIsNotOroEntityListenerResolver(): void
    {
        $oroEventManager = $this->createMock(OroEventManager::class);
        $invalidResolver = new InvalidListenerResolverStub();

        $this->doctrine
            ->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getEventManager')
            ->willReturn($oroEventManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->configuration
            ->expects(self::once())
            ->method('getEntityListenerResolver')
            ->willReturn($invalidResolver);

        $oroEventManager
            ->expects(self::once())
            ->method('disableListeners')
            ->with('.*');

        $draftSessionEventListenersIsolator =
            new DoctrineListenersIsolator($this->doctrine, \stdClass::class);

        $this->expectException(DraftSessionLogicException::class);
        $this->expectExceptionMessage('Entity listener resolver is expected to be an instance of');

        $draftSessionEventListenersIsolator->disableListeners();
    }

    public function testEnableListenersWithOroEventManager(): void
    {
        $oroEventManager = $this->createMock(OroEventManager::class);
        $entityListenerResolver = $this->createMock(OroEntityListenerResolver::class);

        $this->doctrine
            ->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getEventManager')
            ->willReturn($oroEventManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->configuration
            ->expects(self::once())
            ->method('getEntityListenerResolver')
            ->willReturn($entityListenerResolver);

        $oroEventManager
            ->expects(self::once())
            ->method('clearDisabledListeners');

        $entityListenerResolver
            ->expects(self::once())
            ->method('clearDisabledListeners');

        $draftSessionEventListenersIsolator =
            new DoctrineListenersIsolator($this->doctrine, \stdClass::class);

        $draftSessionEventListenersIsolator->enableListeners();
    }

    public function testEnableListenersThrowsWhenEventManagerIsNotOroEventManager(): void
    {
        $plainEventManager = $this->createMock(EventManager::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getEventManager')
            ->willReturn($plainEventManager);

        $draftSessionEventListenersIsolator =
            new DoctrineListenersIsolator($this->doctrine, \stdClass::class);

        $this->expectException(DraftSessionLogicException::class);
        $this->expectExceptionMessage('Event manager is expected to be an instance of');

        $draftSessionEventListenersIsolator->enableListeners();
    }

    public function testEnableListenersThrowsWhenResolverIsNotOroEntityListenerResolver(): void
    {
        $oroEventManager = $this->createMock(OroEventManager::class);
        $invalidResolver = new InvalidListenerResolverStub();

        $this->doctrine
            ->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getEventManager')
            ->willReturn($oroEventManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->configuration
            ->expects(self::once())
            ->method('getEntityListenerResolver')
            ->willReturn($invalidResolver);

        $oroEventManager
            ->expects(self::once())
            ->method('clearDisabledListeners');

        $draftSessionEventListenersIsolator =
            new DoctrineListenersIsolator($this->doctrine, \stdClass::class);

        $this->expectException(DraftSessionLogicException::class);
        $this->expectExceptionMessage('Entity listener resolver is expected to be an instance of');

        $draftSessionEventListenersIsolator->enableListeners();
    }
}
