<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DraftSessionOrmFilterManagerTest extends TestCase
{
    private FilterCollection&MockObject $filterCollection;
    private DraftSessionOrmFilterManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->filterCollection = $this->createMock(FilterCollection::class);

        $doctrine
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::any())
            ->method('getFilters')
            ->willReturn($this->filterCollection);

        $this->manager = new DraftSessionOrmFilterManager($doctrine, 'AcmeEntity', 'acme_draft');
    }

    public function testDisableWhenFilterIsEnabled(): void
    {
        $this->filterCollection
            ->expects(self::once())
            ->method('isEnabled')
            ->with('acme_draft')
            ->willReturn(true);

        $this->filterCollection
            ->expects(self::once())
            ->method('disable')
            ->with('acme_draft');

        $this->manager->disable();
    }

    public function testDisableWhenFilterIsAlreadyDisabled(): void
    {
        $this->filterCollection
            ->expects(self::once())
            ->method('isEnabled')
            ->with('acme_draft')
            ->willReturn(false);

        $this->filterCollection
            ->expects(self::never())
            ->method('disable');

        $this->manager->disable();
    }

    public function testEnableWhenFilterIsDisabled(): void
    {
        $this->filterCollection
            ->expects(self::once())
            ->method('isEnabled')
            ->with('acme_draft')
            ->willReturn(false);

        $this->filterCollection
            ->expects(self::once())
            ->method('enable')
            ->with('acme_draft');

        $this->manager->enable();
    }

    public function testEnableWhenFilterIsAlreadyEnabled(): void
    {
        $this->filterCollection
            ->expects(self::once())
            ->method('isEnabled')
            ->with('acme_draft')
            ->willReturn(true);

        $this->filterCollection
            ->expects(self::never())
            ->method('enable');

        $this->manager->enable();
    }

    public function testIsEnabled(): void
    {
        $this->filterCollection
            ->expects(self::once())
            ->method('isEnabled')
            ->with('acme_draft')
            ->willReturn(true);

        self::assertTrue($this->manager->isEnabled());
    }
}
