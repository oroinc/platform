<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\EntityBundle\Layout\DataProvider\EntityNameLayoutDataProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntityNameLayoutDataProviderTest extends TestCase
{
    private EntityNameResolver&MockObject $entityNameResolver;

    private EntityNameLayoutDataProvider $dataProvider;

    protected function setUp(): void
    {
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->dataProvider = new EntityNameLayoutDataProvider($this->entityNameResolver);
    }

    public function testThatNameReturned(): void
    {
        $this->entityNameResolver
            ->expects(self::any())
            ->method('getName')
            ->willReturn('name');

        self::assertEquals(
            'name',
            $this->dataProvider->getName(new \StdClass(), 'format', 'en')
        );
    }

    public function testThatNullReturned(): void
    {
        self::assertEquals(
            null,
            $this->dataProvider->getName(new \StdClass(), 'format', 'en')
        );
    }
}
