<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProvider;
use PHPUnit\Framework\TestCase;

class EntityOverrideProviderTest extends TestCase
{
    private EntityOverrideProvider $entityOverrideProvider;

    #[\Override]
    protected function setUp(): void
    {
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getSubstitutions')
            ->willReturn([
                'Test\Entity1' => 'Test\Entity2'
            ]);

        $this->entityOverrideProvider = new EntityOverrideProvider($configCache);
    }

    public function testGetSubstituteEntityClass(): void
    {
        self::assertEquals(
            'Test\Entity2',
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity1')
        );
        // test that data is cached in memory
        self::assertEquals(
            'Test\Entity2',
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity1')
        );
    }

    public function testGetSubstituteEntityClassWhenSubstitutionDoesNotExist(): void
    {
        self::assertNull(
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity3')
        );
        // test that data is cached in memory
        self::assertNull(
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity3')
        );
    }

    public function testGetEntityClass(): void
    {
        self::assertEquals(
            'Test\Entity1',
            $this->entityOverrideProvider->getEntityClass('Test\Entity2')
        );
        // test that data is cached in memory
        self::assertEquals(
            'Test\Entity1',
            $this->entityOverrideProvider->getEntityClass('Test\Entity2')
        );
    }

    public function testGetEntityClassWhenSubstitutionDoesNotExist(): void
    {
        self::assertNull(
            $this->entityOverrideProvider->getEntityClass('Test\Entity3')
        );
        // test that data is cached in memory
        self::assertNull(
            $this->entityOverrideProvider->getEntityClass('Test\Entity3')
        );
    }
}
