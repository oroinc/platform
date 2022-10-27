<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumValueProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EnumTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var EnumValueProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->cache = $this->createMock(EnumTranslationCache::class);

        $this->provider = new EnumValueProvider($this->doctrineHelper, $this->cache);
    }

    public function testGetEnumChoicesWithoutCachedValue()
    {
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $expected = ['Test Value' => 'test_val'];
        $repo = $this->createMock(EnumValueRepository::class);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($enumClass, $repo)
            ->willReturn(array_flip($expected));
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);

        self::assertEquals($expected, $this->provider->getEnumChoices($enumClass));
    }

    public function testGetEnumChoicesWithCachedValue()
    {
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $expected = ['Test' => '1'];

        $repo = $this->createMock(EnumValueRepository::class);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($enumClass, $repo)
            ->willReturn(array_flip($expected));
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);

        // We use assertSame here to get a data type proof comparison.
        self::assertSame($expected, $this->provider->getEnumChoices($enumClass));
    }

    public function testGetEnumChoicesByCodeWithoutCachedValue()
    {
        $enumCode = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $expected = ['Test Value' => 'test_val'];

        $repo = $this->createMock(EnumValueRepository::class);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($enumClass, $repo)
            ->willReturn(array_flip($expected));
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);

        self::assertEquals($expected, $this->provider->getEnumChoicesByCode($enumCode));
    }

    public function testGetEnumChoicesByCodeWithCachedValue()
    {
        $enumCode = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $expected = ['Test Value' => 'test_val'];

        $repo = $this->createMock(EnumValueRepository::class);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($enumClass, $repo)
            ->willReturn(array_flip($expected));
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);

        self::assertEquals($expected, $this->provider->getEnumChoicesByCode($enumCode));
    }

    public function testGetEnumValueByCode()
    {
        $enumCode = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $id = 'test_val';
        $value = new TestEnumValue($id, 'Test Value');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with($enumClass, $id)
            ->willReturn($value);

        self::assertSame($value, $this->provider->getEnumValueByCode($enumCode, $id));
    }

    public function testGetDefaultEnumValues()
    {
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $value = new TestEnumValue('test_val', 'Test Value');

        $repo = $this->createMock(EnumValueRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([$value]);

        self::assertSame([$value], $this->provider->getDefaultEnumValues($enumClass));
    }

    public function testGetDefaultEnumValuesByCode()
    {
        $enumCode = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $value = new TestEnumValue('test_val', 'Test Value');

        $repo = $this->createMock(EnumValueRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([$value]);

        self::assertSame([$value], $this->provider->getDefaultEnumValuesByCode($enumCode));
    }

    public function testGetDefaultEnumValue()
    {
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $value = new TestEnumValue('test_val', 'Test Value');

        $repo = $this->createMock(EnumValueRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([$value]);

        self::assertSame($value, $this->provider->getDefaultEnumValue($enumClass));
    }

    public function testGetDefaultEnumValueWhenNoDefaultEnumValues()
    {
        $enumClass = 'Extend\Entity\EV_Test_Enum';

        $repo = $this->createMock(EnumValueRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([]);

        self::assertNull($this->provider->getDefaultEnumValue($enumClass));
    }

    public function testGetDefaultEnumValueByCode()
    {
        $enumCode = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $value = new TestEnumValue('test_val', 'Test Value');

        $repo = $this->createMock(EnumValueRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([$value]);

        self::assertSame($value, $this->provider->getDefaultEnumValueByCode($enumCode));
    }

    public function testGetDefaultEnumValueByCodeWhenNoDefaultEnumValues()
    {
        $enumCode = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';

        $repo = $this->createMock(EnumValueRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getDefaultValues')
            ->willReturn([]);

        self::assertNull($this->provider->getDefaultEnumValueByCode($enumCode));
    }
}
