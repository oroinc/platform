<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class EnumTranslationCacheTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'FooBar';
    private const LOCALE = 'en_US';

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var EnumTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    private $enumTranslationCache;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var LocaleSettings */
    private $localeSettings;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->enumTranslationCache = new EnumTranslationCache(
            $this->cache,
            $this->localizationHelper,
            $this->localeSettings
        );
    }

    public function testGet()
    {
        $localization = (new Localization())->setFormattingCode(self::LOCALE);
        $this->localizationHelper->expects($this->exactly(2))
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $expected = ['test_val' => 'Test Value'];
        $repo = $this->createMock(EnumValueRepository::class);
        $repo->expects(self::once())
            ->method('getValues')
            ->willReturn([new TestEnumValue('test_val', 'Test Value')]);
        $this->cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey($enumClass . '|' . self::LOCALE))
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        self::assertEquals($expected, $this->enumTranslationCache->get($enumClass, $repo));
    }

    public function testGetCached()
    {
        $localization = (new Localization())->setFormattingCode(self::LOCALE);
        $this->localizationHelper->expects($this->exactly(2))
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $expected = ['test_val' => 'Test Value'];
        $repo = $this->createMock(EnumValueRepository::class);
        $repo->expects(self::never())
            ->method('getValues');
        $this->cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey($enumClass . '|' . self::LOCALE))
            ->willReturn($expected);
        self::assertEquals($expected, $this->enumTranslationCache->get($enumClass, $repo));
    }

    public function testInvalidate()
    {
        $key = $this->getKey();
        $localization = new Localization();
        $localization->setFormattingCode(self::LOCALE);
        $this->localizationHelper->expects($this->once())
            ->method('getLocalizations')
            ->willReturn([$localization]);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with($key);

        $this->enumTranslationCache->invalidate(self::CLASS_NAME);
    }

    private function getKey(): string
    {
        return sprintf('%s|%s', self::CLASS_NAME, self::LOCALE);
    }
}
