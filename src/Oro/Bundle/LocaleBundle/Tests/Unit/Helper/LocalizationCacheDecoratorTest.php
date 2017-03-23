<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Doctrine\Common\Cache\ArrayCache;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationCacheDecorator;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Serializer\LocalizationArraySerializer;
use Oro\Bundle\TranslationBundle\Entity\Language;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationCacheDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var LocalizationArraySerializer
     */
    private $localizationArraySerializer;

    public function setUp()
    {
        $this->localizationArraySerializer = new LocalizationArraySerializer();
    }

    /**
     * @param Localization[] $localizations
     * @dataProvider localizationsDataProvider
     */
    public function testFetch(array $localizations)
    {
        $cacheProvider = $this->getArrayCache($localizations);

        $localizationCacheHelper = new LocalizationCacheDecorator(
            $cacheProvider,
            $this->localizationArraySerializer
        );

        $result = $localizationCacheHelper->fetch(LocalizationManager::CACHE_NAMESPACE);
        $this->assertCount(count($localizations), $result);

        foreach ($result as $i => $localization) {
            $this->assertInstanceOf(Localization::class, $localization);
            $this->assertEquals($localizations[$i], $localization);
        }
    }

    public function testFetchWithParentLocalization()
    {
        $parent = $this->getEntity(Localization::class, [
            'id' => 1,
            'language' => $this->getEntity(Language::class, ['code' => 'ua'])
        ]);
        /** @var Localization $child */
        $child = $this->getEntity(Localization::class, [
            'id' => 2,
            'language' => $this->getEntity(Language::class, ['code' => 'ru'])
        ]);
        $child->setParentLocalization($parent);

        $localizations = [1 => $parent, 2 => $child];

        $cacheProvider = $this->getArrayCache($localizations);

        $localizationCacheHelper = new LocalizationCacheDecorator(
            $cacheProvider,
            $this->localizationArraySerializer
        );

        $result = $localizationCacheHelper->fetch(LocalizationManager::CACHE_NAMESPACE);
        $this->assertCount(count($localizations), $result);

        foreach ($result as $i => $localization) {
            $this->assertInstanceOf(Localization::class, $localization);
            $this->assertEquals($localizations[$i], $localization);
        }
    }

    /**
     * @param Localization[] $localizations
     * @dataProvider localizationsDataProvider
     */
    public function testSave(array $localizations)
    {
        $cacheProvider = $this->getArrayCache();

        $localizationCacheHelper = new LocalizationCacheDecorator(
            $cacheProvider,
            $this->localizationArraySerializer
        );

        $localizationCacheHelper->save(LocalizationManager::CACHE_NAMESPACE, $localizations);

        $this->assertEquals(
            $this->serializeLocalizations($localizations),
            $cacheProvider->fetch(LocalizationManager::CACHE_NAMESPACE)
        );
    }

    /**
     * @return array
     */
    public function localizationsDataProvider()
    {
        return [
            'singleLocalization' => [
                [
                    10 => $this->getEntity(Localization::class, [
                        'id' => 10,
                        'language' => $this->getEntity(Language::class, ['code' => 'en'])
                    ])
                ]
            ],
            'twoLocalizationsWithKeys' => [
                [
                    1 => $this->getEntity(Localization::class, [
                        'id' => 1,
                        'language' => $this->getEntity(Language::class, ['code' => 'en'])
                    ]),
                    5 => $this->getEntity(Localization::class, [
                        'id' => 5,
                        'language' => $this->getEntity(Language::class, ['code' => 'en'])
                    ]),
                ]
            ],
            'twoLocalizationsWithoutKeys' => [
                [
                    $this->getEntity(Localization::class, [
                        'id' => 1,
                        'language' => $this->getEntity(Language::class, ['code' => 'ru'])
                    ]),
                    $this->getEntity(Localization::class, [
                        'id' => 2,
                        'language' => $this->getEntity(Language::class, ['code' => 'ua'])
                    ]),
                ]
            ]
        ];
    }

    /**
     * @param Localization[]|null $localizations Set it if you want cache to be filled
     * @return ArrayCache
     */
    private function getArrayCache(array $localizations = null)
    {
        $arrayCache = new ArrayCache();

        if ($localizations) {
            $arrayCache->save(
                LocalizationManager::CACHE_NAMESPACE,
                $this->serializeLocalizations($localizations)
            );
        }

        return $arrayCache;
    }

    /**
     * @param Localization[] $localizations
     * @return string[]
     */
    private function serializeLocalizations(array $localizations)
    {
        return array_map(function ($element) {
            return $this->localizationArraySerializer->serialize($element);
        }, $localizations);
    }
}
