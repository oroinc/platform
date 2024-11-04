<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    #[\Override]
    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->enumTranslationCache = new EnumTranslationCache(
            $this->cache,
            $this->localizationHelper,
            $this->localeSettings,
            $this->translator
        );
    }

    public function testGet()
    {
        $localization = (new Localization())->setFormattingCode(self::LOCALE);
        $this->localizationHelper->expects($this->exactly(2))
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $expected = ['test_enum_code.test_val' => 'Test Value'];
        $repo = $this->createMock(EnumOptionRepository::class);
        $repo->expects(self::once())
            ->method('getValues')
            ->willReturn([new TestEnumValue('test_enum_code', 'Test Value', 'test_val')]);
        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('Test Value');
        $this->cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey(EnumOption::class . '|' . self::LOCALE))
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        self::assertEquals($expected, $this->enumTranslationCache->get(EnumOption::class, $repo));
    }

    public function testGetCached()
    {
        $localization = (new Localization())->setFormattingCode(self::LOCALE);
        $this->localizationHelper->expects($this->exactly(2))
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $enumCode = 'test_enum_code';
        $expected = ['test_val' => 'Test Value'];
        $repo = $this->createMock(EnumOptionRepository::class);
        $repo->expects(self::never())
            ->method('getValues');
        $this->cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey($enumCode . '|' . self::LOCALE))
            ->willReturn($expected);
        self::assertEquals($expected, $this->enumTranslationCache->get($enumCode, $repo));
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
