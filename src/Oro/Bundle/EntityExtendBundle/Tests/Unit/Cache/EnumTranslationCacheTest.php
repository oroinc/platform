<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class EnumTranslationCacheTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = 'FooBar';
    const LOCALE = 'en_US';

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var EnumTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    private $enumTranslationCache;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var LocaleSettings */
    private $localeSettings;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);
        $this->translator
            ->expects($this->never())
            ->method('getLocale');

        $this->cache = $this->createMock(Cache::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->enumTranslationCache = new EnumTranslationCache($this->translator, $this->cache);
        $this->enumTranslationCache->setLocalizationHelper($this->localizationHelper);
        $this->enumTranslationCache->setLocaleSettings($this->localeSettings);
    }

    /**
     * @dataProvider getDataForContains
     */
    public function testContains(bool $isContains, bool $expected)
    {
        $localization = (new Localization())->setFormattingCode(self::LOCALE);
        $this->localizationHelper
            ->expects($this->any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->cache->expects($this->once())
            ->method('contains')
            ->with($this->getKey())
            ->willReturn($isContains);

        $this->assertEquals($expected, $this->enumTranslationCache->contains(self::CLASS_NAME));
    }

    public function getDataForContains(): array
    {
        return [
            'not contains' => [
                'isContains' => false,
                'expected' => false
            ],
            'contains' => [
                'isContains' => true,
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider getDataForFetch
     */
    public function testFetch(bool $isContains, array $values)
    {
        $key = $this->getKey();
        $this->localeSettings
            ->expects($this->any())
            ->method('getLocale')
            ->willReturn(self::LOCALE);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn($isContains ? $values : false);

        $this->assertEquals($values, $this->enumTranslationCache->fetch(self::CLASS_NAME));
    }

    public function getDataForFetch(): array
    {
        return [
            'not contains' => [
                'isContains' => false,
                'values' => []
            ],
            'contains empty' => [
                'isContains' => true,
                'values' => []
            ],
            'contains values' => [
                'isContains' => true,
                'values' => [
                    ['value' => 1],
                    ['value' => 2]
                ]
            ]
        ];
    }

    public function testSave()
    {
        $key = $this->getKey();
        $values = [
            ['value' => 1],
            ['value' => 2]
        ];

        $this->localeSettings
            ->expects($this->any())
            ->method('getLocale')
            ->willReturn(self::LOCALE);

        $this->cache->expects($this->once())
            ->method('save')
            ->with($key);

        $this->enumTranslationCache->save(self::CLASS_NAME, $values);
    }

    public function testInvalidate()
    {
        $key = $this->getKey();
        $localization = new Localization();
        $localization->setFormattingCode(self::LOCALE);
        $this->localizationHelper
            ->expects($this->once())
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
