<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Doctrine\Common\Cache\ArrayCache;

use JMS\Serializer\SerializerInterface;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationCacheDecorator;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\TranslationBundle\Entity\Language;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationCacheDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    public function setUp()
    {
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
    }

    public function testFetchWithNoLocalizationsFromCache()
    {
        $cacheProvider = $this->getArrayCache();

        $localizationCacheDecorator = new LocalizationCacheDecorator(
            $cacheProvider,
            $this->serializer
        );

        $this->assertFalse($localizationCacheDecorator->fetch(LocalizationManager::CACHE_NAMESPACE));
    }

    /**
     * @param Localization[] $localizations
     * @param array          $localizationsFromCache
     * @dataProvider localizationsDataProvider
     */
    public function testFetch(array $localizations, array $localizationsFromCache)
    {
        $cacheProvider = $this->getArrayCache($localizationsFromCache);

        $localizationMap = [];
        foreach ($localizationsFromCache as $id => $localizationFromCache) {
            $localizationMap[] = [
                $localizationFromCache,
                Localization::class,
                LocalizationCacheDecorator::SERIALIZATION_FORMAT,
                null,
                $localizations[$id]
            ];
        }
        $this->serializer->expects($this->exactly(count($localizationsFromCache)))
            ->method('deserialize')
            ->willReturnMap($localizationMap);

        $localizationCacheDecorator = new LocalizationCacheDecorator(
            $cacheProvider,
            $this->serializer
        );

        $result = $localizationCacheDecorator->fetch(LocalizationManager::CACHE_NAMESPACE);
        $this->assertCount(count($localizations), $result);

        foreach ($result as $i => $localization) {
            $this->assertInstanceOf(Localization::class, $localization);
            $this->assertEquals($localizations[$i], $localization);
        }
    }

    /**
     * @param Localization[] $localizations
     * @param array          $localizationsFromCache
     * @dataProvider localizationsDataProvider
     */
    public function testSave(array $localizations, array $localizationsFromCache)
    {
        $cacheProvider = $this->getArrayCache();

        $this->serializer->expects($this->exactly(count($localizations)))
            ->method('serialize')
            ->willReturnOnConsecutiveCalls(...$localizationsFromCache);

        $localizationCacheDecorator = new LocalizationCacheDecorator(
            $cacheProvider,
            $this->serializer
        );

        $localizationCacheDecorator->save(LocalizationManager::CACHE_NAMESPACE, $localizations);

        $this->assertEquals(
            $localizationsFromCache,
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
                ],
                [10 => ['id' => 10, 'language' => ['code' => 'en']]]
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
                ],
                [1 => ['id' => 1, 'language' => ['code' => 'en']], 5 => ['id' => 5, 'language' => ['code' => 'en']]]
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
                ],
                [['id' => 1, 'language' => ['code' => 'ru']], ['id' => 2, 'language' => ['code' => 'ua']]]
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
                $localizations
            );
        }

        return $arrayCache;
    }
}
