<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Provider\LocalizedFallbackValueFieldsProvider;

class LocalizedFallbackValueFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    private LocalizedFallbackValueFieldsProvider $provider;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->provider = new LocalizedFallbackValueFieldsProvider($managerRegistry);

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
    }

    /**
     * @dataProvider getLocalizedFallbackValueFieldsDataProvider
     *
     * @param array $associationMappings
     * @param array $expected
     */
    public function testGetLocalizedFallbackValueFields(array $associationMappings, array $expected): void
    {
        $classMetadata = new ClassMetadataInfo(\stdClass::class);
        $classMetadata->associationMappings = $associationMappings;
        $this->entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($classMetadata);

        self::assertEquals($expected, $this->provider->getLocalizedFallbackValueFields(\stdClass::class));
    }

    public function getLocalizedFallbackValueFieldsDataProvider(): array
    {
        return [
            'no associations' => [
                'associationMappings' => [],
                'expected' => [],
            ],
            'not localized fallback value association' => [
                'associationMappings' => [
                    'sample_field' => ['targetEntity' => \stdClass::class],
                ],
                'expected' => [],
            ],
            'localized fallback value association' => [
                'associationMappings' => [
                    'sample_field' => ['targetEntity' => $this->getMockClass(AbstractLocalizedFallbackValue::class)],
                ],
                'expected' => ['sample_field'],
            ],
        ];
    }
}
