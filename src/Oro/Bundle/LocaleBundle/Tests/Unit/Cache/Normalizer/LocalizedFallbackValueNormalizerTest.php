<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Cache\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalizedFallbackValueNormalizerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private LocalizedFallbackValueNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->expects(self::any())
            ->method('getReference')
            ->with(Localization::class)
            ->willReturnCallback(function (string $class, int $id) {
                return new LocalizationStub($id);
            });

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->normalizer = new LocalizedFallbackValueNormalizer(
            ['id' => 'i', 'string' => 's', 'localization' => 'l', 'fallback' => 'f'],
            $doctrine
        );
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

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(AbstractLocalizedFallbackValue $localizedFallbackValue, array $expected): void
    {
        $className = ClassUtils::getRealClass(get_class($localizedFallbackValue));

        $this->entityManager->expects(self::once())
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
                'expected' => []
            ],
            'with fields' => [
                'localizedFallbackValue' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setFallback(FallbackType::SYSTEM),
                'expected' => ['s' => 'sample string', 'f' => FallbackType::SYSTEM]
            ],
            'with localization' => [
                'localizedFallbackValue' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setFallback(FallbackType::NONE)
                    ->setLocalization(new LocalizationStub(42)),
                'expected' => ['s' => 'sample string', 'l' => 42]
            ],
            'with custom class' => [
                'localizedFallbackValue' => (new CustomLocalizedFallbackValueStub())
                    ->setString('sample string')
                    ->setFallback(FallbackType::NONE)
                    ->setLocalization(new LocalizationStub(42)),
                'expected' => ['s' => 'sample string', 'l' => 42]
            ]
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
        $this->entityManager->expects(self::once())
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
                'expected' => new LocalizedFallbackValue()
            ],
            'with fields' => [
                'normalizedData' => ['s' => 'sample string', 'f' => FallbackType::SYSTEM],
                'className' => LocalizedFallbackValue::class,
                'expected' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setFallback(FallbackType::SYSTEM)
            ],
            'with fields (old format)' => [
                'normalizedData' => ['string' => 'sample string', 'fallback' => FallbackType::SYSTEM],
                'className' => LocalizedFallbackValue::class,
                'expected' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setFallback(FallbackType::SYSTEM)
            ],
            'with localization' => [
                'normalizedData' => ['s' => 'sample string', 'f' => FallbackType::NONE, 'l' => 42],
                'className' => LocalizedFallbackValue::class,
                'expected' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setLocalization(new LocalizationStub(42))
            ],
            'with localization (old format)' => [
                'normalizedData' => [
                    'string' => 'sample string',
                    'fallback' => FallbackType::NONE,
                    'localization' => ['id' => 42]
                ],
                'className' => LocalizedFallbackValue::class,
                'expected' => (new LocalizedFallbackValue())
                    ->setString('sample string')
                    ->setLocalization(new LocalizationStub(42))
            ],
            'with custom class' => [
                'normalizedData' => ['s' => 'sample string', 'f' => FallbackType::NONE, 'l' => 42],
                'className' => CustomLocalizedFallbackValueStub::class,
                'expected' => (new CustomLocalizedFallbackValueStub())
                    ->setString('sample string')
                    ->setLocalization(new LocalizationStub(42))
            ],
            'with custom class (old format)' => [
                'normalizedData' => [
                    'string' => 'sample string',
                    'fallback' => FallbackType::NONE,
                    'localization' => ['id' => 42]
                ],
                'className' => CustomLocalizedFallbackValueStub::class,
                'expected' => (new CustomLocalizedFallbackValueStub())
                    ->setString('sample string')
                    ->setLocalization(new LocalizationStub(42))
            ]
        ];
    }
}
