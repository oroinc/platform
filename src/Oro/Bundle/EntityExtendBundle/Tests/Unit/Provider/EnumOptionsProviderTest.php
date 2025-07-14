<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumOptionsProviderTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private EnumTranslationCache&MockObject $cache;
    private EnumOptionsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->cache = $this->createMock(EnumTranslationCache::class);

        $this->provider = new EnumOptionsProvider($this->doctrineHelper, $this->cache);
    }

    public function testGetEnumChoicesWithoutCachedValue(): void
    {
        $enumClass = EnumOption::class;
        $expected = ['Test Value' => 'test_val'];
        $repo = $this->createMock(EnumOptionRepository::class);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($enumClass, $repo)
            ->willReturn(array_flip($expected));
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);

        self::assertEquals($expected, $this->provider->getEnumChoicesByCode($enumClass));
    }

    public function testGetEnumChoicesWithCachedValue(): void
    {
        $enumClass = EnumOption::class;
        $expected = ['Test' => '1'];

        $repo = $this->createMock(EnumOptionRepository::class);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($enumClass, $repo)
            ->willReturn(array_flip($expected));
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);

        // We use assertSame here to get a data type proof comparison.
        self::assertSame($expected, $this->provider->getEnumChoicesByCode($enumClass));
    }

    public function testGetEnumChoicesByCodeWithoutCachedValue(): void
    {
        $enumCode = 'test_enum';
        $enumClass = EnumOption::class;
        $expected = ['Test Value' => 'test_val'];

        $repo = $this->createMock(EnumOptionRepository::class);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($enumCode, $repo)
            ->willReturn(array_flip($expected));
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);

        self::assertEquals($expected, $this->provider->getEnumChoicesByCode($enumCode));
    }

    public function testGetEnumChoicesByCodeWithCachedValue(): void
    {
        $enumCode = 'test_enum';
        $enumClass = EnumOption::class;
        $expected = ['Test Value' => 'test_val'];

        $repo = $this->createMock(EnumOptionRepository::class);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($enumCode, $repo)
            ->willReturn(array_flip($expected));
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);

        self::assertEquals($expected, $this->provider->getEnumChoicesByCode($enumCode));
    }

    public function testGetEnumValueByCode(): void
    {
        $enumCode = 'test_enum_code';
        $enumClass = EnumOption::class;
        $id = ExtendHelper::buildEnumOptionId($enumCode, 'test_val');
        $value = new TestEnumValue(
            $enumCode,
            'Test',
            'test',
            1
        );

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with($enumClass, $id)
            ->willReturn($value);

        self::assertSame($value, $this->provider->getEnumOptionByCode($enumCode, 'test_val'));
    }

    public function testGetDefaultEnumValues(): void
    {
        $enumClass = EnumOption::class;
        $value = new TestEnumValue('test_enum_code', 'Test', 'test', 1);

        $repo = $this->createMock(EnumOptionRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([$value]);

        self::assertSame([$value], $this->provider->getDefaultEnumOptions($enumClass));
    }

    public function testGetDefaultEnumValuesByCode(): void
    {
        $enumCode = 'test_enum_code';
        $enumClass = EnumOption::class;
        $value = new TestEnumValue(
            $enumCode,
            'Test',
            'test',
            1
        );

        $repo = $this->createMock(EnumOptionRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([$value]);

        self::assertSame([$value], $this->provider->getDefaultEnumOptionsByCode($enumCode));
    }

    public function testGetDefaultEnumValue(): void
    {
        $enumClass = EnumOption::class;
        $value = new TestEnumValue('test_enum_code', 'Test', 'test', 1);

        $repo = $this->createMock(EnumOptionRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([$value]);

        self::assertSame($value, $this->provider->getDefaultEnumOptionByCode($enumClass));
    }

    public function testGetDefaultEnumValueWhenNoDefaultEnumValues(): void
    {
        $enumClass = EnumOption::class;

        $repo = $this->createMock(EnumOptionRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([]);

        self::assertNull($this->provider->getDefaultEnumOptionByCode($enumClass));
    }

    public function testGetDefaultEnumValueByCode(): void
    {
        $enumCode = 'test_enum_code';
        $enumClass = EnumOption::class;
        $value = new TestEnumValue(
            $enumCode,
            'Test',
            'test',
            1
        );

        $repo = $this->createMock(EnumOptionRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([$value]);

        self::assertSame($value, $this->provider->getDefaultEnumOptionByCode($enumCode));
    }

    public function testGetDefaultEnumValueByCodeWhenNoDefaultEnumValues(): void
    {
        $enumCode = 'test_enum';
        $enumClass = EnumOption::class;

        $repo = $this->createMock(EnumOptionRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([]);

        self::assertNull($this->provider->getDefaultEnumOptionByCode($enumCode));
    }
}
