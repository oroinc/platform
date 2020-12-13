<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class ChainEntityClassNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityClassNameProviderInterface[]|\PHPUnit\Framework\MockObject\MockObject[] */
    private $providers = [];

    /** @var ChainEntityClassNameProvider */
    private $chainProvider;

    protected function setUp(): void
    {
        $this->providers = [
            $this->createMock(EntityClassNameProviderInterface::class),
            $this->createMock(EntityClassNameProviderInterface::class)
        ];

        $this->chainProvider = new ChainEntityClassNameProvider($this->providers);
    }

    public function testGetEntityClassNameByFirstProvider()
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

    public function testGetEntityClassNameBySecondProvider()
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

    public function testGetEntityClassNameWhenNoProvidersThatCanReturnIt()
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

    public function testGetEntityClassPluralNameByFirstProvider()
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

    public function testGetEntityClassPluralNameBySecondProvider()
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

    public function testGetEntityClassPluralNameWhenNoProvidersThatCanReturnIt()
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
