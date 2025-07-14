<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainEntityClassNameProviderTest extends TestCase
{
    /** @var EntityClassNameProviderInterface[]&MockObject[] */
    private $providers = [];
    private ChainEntityClassNameProvider $chainProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->providers = [
            $this->createMock(EntityClassNameProviderInterface::class),
            $this->createMock(EntityClassNameProviderInterface::class)
        ];

        $this->chainProvider = new ChainEntityClassNameProvider($this->providers);
    }

    public function testGetEntityClassNameByFirstProvider(): void
    {
        $entityClass = 'Test\Entity';

        $this->providers[0]->expects(self::once())
            ->method('getEntityClassName')
            ->with($entityClass)
            ->willReturn('Test Entity 1');
        $this->providers[1]->expects(self::never())
            ->method('getEntityClassName');

        self::assertEquals('Test Entity 1', $this->chainProvider->getEntityClassName($entityClass));
    }

    public function testGetEntityClassNameBySecondProvider(): void
    {
        $entityClass = 'Test\Entity';

        $this->providers[0]->expects(self::once())
            ->method('getEntityClassName')
            ->with($entityClass)
            ->willReturn(null);
        $this->providers[1]->expects(self::once())
            ->method('getEntityClassName')
            ->with($entityClass)
            ->willReturn('Test Entity 2');

        self::assertEquals('Test Entity 2', $this->chainProvider->getEntityClassName($entityClass));
    }

    public function testGetEntityClassNameWhenNoProvidersThatCanReturnIt(): void
    {
        $entityClass = 'Test\Entity';

        $this->providers[0]->expects(self::once())
            ->method('getEntityClassName')
            ->with($entityClass)
            ->willReturn(null);
        $this->providers[1]->expects(self::once())
            ->method('getEntityClassName')
            ->with($entityClass)
            ->willReturn(null);

        self::assertNull($this->chainProvider->getEntityClassName($entityClass));
    }

    public function testGetEntityClassPluralNameByFirstProvider(): void
    {
        $entityClass = 'Test\Entity';

        $this->providers[0]->expects(self::once())
            ->method('getEntityClassPluralName')
            ->with($entityClass)
            ->willReturn('Test Entity 1');
        $this->providers[1]->expects(self::never())
            ->method('getEntityClassPluralName');

        self::assertEquals('Test Entity 1', $this->chainProvider->getEntityClassPluralName($entityClass));
    }

    public function testGetEntityClassPluralNameBySecondProvider(): void
    {
        $entityClass = 'Test\Entity';

        $this->providers[0]->expects(self::once())
            ->method('getEntityClassPluralName')
            ->with($entityClass)
            ->willReturn(null);
        $this->providers[1]->expects(self::once())
            ->method('getEntityClassPluralName')
            ->with($entityClass)
            ->willReturn('Test Entity 2');

        self::assertEquals('Test Entity 2', $this->chainProvider->getEntityClassPluralName($entityClass));
    }

    public function testGetEntityClassPluralNameWhenNoProvidersThatCanReturnIt(): void
    {
        $entityClass = 'Test\Entity';

        $this->providers[0]->expects(self::once())
            ->method('getEntityClassPluralName')
            ->with($entityClass)
            ->willReturn(null);
        $this->providers[1]->expects(self::once())
            ->method('getEntityClassPluralName')
            ->with($entityClass)
            ->willReturn(null);

        self::assertNull($this->chainProvider->getEntityClassPluralName($entityClass));
    }
}
