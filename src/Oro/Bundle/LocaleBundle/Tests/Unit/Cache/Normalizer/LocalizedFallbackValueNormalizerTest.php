<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Cache\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueNormalizer;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\CustomLocalizedFallbackValueStub;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;

class LocalizedFallbackValueNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    private LocalizedFallbackValueNormalizer $normalizer;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->normalizer = new LocalizedFallbackValueNormalizer($managerRegistry);

        $this->entityManager
            ->expects(self::any())
            ->method('getReference')
            ->with(Localization::class)
            ->willReturnCallback(static fn ($class, $id) => new LocalizationStub($id));
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(AbstractLocalizedFallbackValue $localizedFallbackValue, array $expected): void
    {
        $className = ClassUtils::getRealClass(get_class($localizedFallbackValue));

        $this->entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($this->createClassMetadata($className));

        self::assertEquals($expected, $this->normalizer->normalize($localizedFallbackValue));

        // Checks class metadata local cache.
        self::assertEquals($expected, $this->normalizer->normalize($localizedFallbackValue));
    }

    public function normalizeDataProvider(): array
    {
        return [
            'empty' => [
                'localizedFallbackValue' => new LocalizedFallbackValue(),
                'expected' => [],
            ],
            'with fields' => [
                'localizedFallbackValue' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setFallback(FallbackType::SYSTEM),
                'expected' => [
                    'string' => 'sample string',
                    'fallback' => FallbackType::SYSTEM,
                ],
            ],
            'with localization' => [
                'localizedFallbackValue' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setFallback(FallbackType::NONE)
                    ->setLocalization(new LocalizationStub(42)),
                'expected' => [
                    'string' => 'sample string',
                    'localization' => ['id' => 42]
                ],
            ],
            'with custom class' => [
                'localizedFallbackValue' => (new CustomLocalizedFallbackValueStub())
                    ->setString('sample string')
                    ->setFallback(FallbackType::NONE)
                    ->setLocalization(new LocalizationStub(42)),
                'expected' => [
                    'string' => 'sample string',
                    'localization' => ['id' => 42]
                ],
            ],
        ];
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(
        array $normalizedData,
        string $className,
        AbstractLocalizedFallbackValue $expected
    ): void {
        $this->entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($this->createClassMetadata($className));

        self::assertEquals($expected, $this->normalizer->denormalize($normalizedData, $className));

        // Checks class metadata local cache.
        self::assertEquals($expected, $this->normalizer->denormalize($normalizedData, $className));
    }

    public function denormalizeDataProvider(): array
    {
        return [
            'empty' => [
                'normalizedData' => [],
                'className' => LocalizedFallbackValue::class,
                'expected' => new LocalizedFallbackValue(),
            ],
            'with fields' => [
                'normalizedData' => [
                    'string' => 'sample string',
                    'fallback' => FallbackType::SYSTEM,
                ],
                'className' => LocalizedFallbackValue::class,
                'expected' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setFallback(FallbackType::SYSTEM),
            ],
            'with localization' => [
                'normalizedData' => [
                    'string' => 'sample string',
                    'fallback' => FallbackType::NONE,
                    'localization' => ['id' => 42],
                ],
                'className' => LocalizedFallbackValue::class,
                'expected' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setLocalization(new LocalizationStub(42)),
            ],
            'with custom class' => [
                'normalizedData' => [
                    'string' => 'sample string',
                    'fallback' => FallbackType::NONE,
                    'localization' => ['id' => 42],
                ],
                'className' => CustomLocalizedFallbackValueStub::class,
                'expected' => (new CustomLocalizedFallbackValueStub())
                    ->setString('sample string')
                    ->setLocalization(new LocalizationStub(42)),
            ],
        ];
    }

    private function createClassMetadata(string $className): ClassMetadata
    {
        $classMetadata = new ClassMetadata($className);

        $classMetadata->mapField(['fieldName' => 'id']);
        $classMetadata->mapField(['fieldName' => 'string']);
        $classMetadata->mapField(['fieldName' => 'fallback']);
        $classMetadata->wakeupReflection(new RuntimeReflectionService());

        return $classMetadata;
    }
}
