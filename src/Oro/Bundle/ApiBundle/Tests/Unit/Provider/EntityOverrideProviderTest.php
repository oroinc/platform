<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProvider;

class EntityOverrideProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCache */
    private $configCache;

    /** @var EntityOverrideProvider */
    private $entityOverrideProvider;

    protected function setUp()
    {
        $this->configCache = $this->createMock(ConfigCache::class);
        $this->configCache->expects(self::once())
            ->method('getSubstitutions')
            ->willReturn([
                'Test\Entity1' => 'Test\Entity2'
            ]);
        $this->entityOverrideProvider = new EntityOverrideProvider($this->configCache);
    }

    public function testGetSubstituteEntityClass()
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

    public function testGetSubstituteEntityClassWhenSubstitutionDoesNotExist()
    {
        self::assertNull(
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity3')
        );
        // test that data is cached in memory
        self::assertNull(
            $this->entityOverrideProvider->getSubstituteEntityClass('Test\Entity3')
        );
    }
}
