<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Doctrine\Common\Cache\ArrayCache;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationCacheDecorator;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationCacheDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @param Localization[] $localizations
     * @dataProvider getLocalizations
     */
    public function testFetch(array $localizations)
    {
        $cacheProvider =new ArrayCache();
        $cacheProvider->save(LocalizationManager::CACHE_NAMESPACE, $this->serializeLocalizations($localizations));

        $localizationCacheHelper = new LocalizationCacheDecorator($cacheProvider);

        $result = $localizationCacheHelper->fetch(LocalizationManager::CACHE_NAMESPACE);
        $this->assertCount(count($localizations), $result);

        foreach ($result as $i => $localization) {
            $this->assertInstanceOf(Localization::class, $localization);
            $this->assertEquals($localizations[$i], $localization);
        }
    }

    /**
     * @param Localization[] $localizations
     * @dataProvider getLocalizations
     */
    public function testSave(array $localizations)
    {
        $cacheProvider = new ArrayCache();

        $localizationCacheHelper = new LocalizationCacheDecorator($cacheProvider);

        $localizationCacheHelper->save(LocalizationManager::CACHE_NAMESPACE, $localizations);

        $this->assertEquals(
            $this->serializeLocalizations($localizations),
            $cacheProvider->fetch(LocalizationManager::CACHE_NAMESPACE)
        );
    }

    /**
     * @return array
     */
    public function getLocalizations()
    {
        return [
            [
                [10 => $this->getEntity(Localization::class, ['id' => 10])]
            ],
            [
                [
                    1 => $this->getEntity(Localization::class, ['id' => 1]),
                    5 => $this->getEntity(Localization::class, ['id' => 5]),
                ]
            ],
            [
                [
                    $this->getEntity(Localization::class, ['id' => 1]),
                    $this->getEntity(Localization::class, ['id' => 2]),
                ]
            ]
        ];
    }

    /**
     * @param Localization[] $localizations
     * @return string[]
     * TODO this should be deleted and properly mocked while doing @BAP-13604
     */
    private function serializeLocalizations(array $localizations)
    {
        return array_map(function ($element) {
            return serialize($element);
        }, $localizations);
    }
}
