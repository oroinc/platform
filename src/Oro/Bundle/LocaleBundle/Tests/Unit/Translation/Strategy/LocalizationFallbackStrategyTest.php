<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Translation\Strategy;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;

class LocalizationFallbackStrategyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var LocalizationFallbackStrategy
     */
    protected $strategy;

    protected function setUp()
    {
        $this->doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'contains', 'save', 'delete'])->getMockForAbstractClass();
        $this->strategy = new LocalizationFallbackStrategy($this->doctrine, $this->cache);
        $this->strategy->setEntityClass('Oro\Bundle\LocaleBundle\Entity\Localization');
    }

    /**
     * @dataProvider getLocaleFallbacksDataProvider
     *
     * @param array|null $entities
     * @param array $localizations
     */
    public function testGetLocaleFallbacks($entities, array $localizations)
    {
        $this->cache->expects($this->once())
            ->method('contains')
            ->with(LocalizationFallbackStrategy::CACHE_KEY)
            ->willReturn(false);
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\LocaleBundle\Entity\Localization')
            ->willReturn($em);
        /** @var LocalizationRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository')
            ->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with('Oro\Bundle\LocaleBundle\Entity\Localization')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findRootsWithChildren')
            ->willReturn($entities);
        $repository->expects($this->once())
            ->method('findRootsWithChildren')
            ->willReturn($entities);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(LocalizationFallbackStrategy::CACHE_KEY, $localizations)
            ->willReturn((bool)$entities);
        $this->cache->expects($this->never())
            ->method('fetch');
        $this->assertEquals($localizations, $this->strategy->getLocaleFallbacks());
    }

    /**
     * @return array
     */
    public function getLocaleFallbacksDataProvider()
    {
        $secondLevelLevelEn = $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization', [
            'languageCode' => 'en',
            'formattingCode' => 'en_FR',
        ]);
        $firstLevelEn = $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization', [
            'languageCode' => 'en',
            'formattingCode' => 'en_EN',
            'childLocalizations' => new ArrayCollection([$secondLevelLevelEn])]
        );
        $en = $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization', [
            'languageCode' => 'en',
            'formattingCode' => 'en',
            'childLocalizations' => new ArrayCollection([$firstLevelEn])
        ]);
        $firstLevelRu = $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization', [
            'languageCode' => 'ru',
            'formattingCode' => 'ru_RU',
        ]);
        $ru = $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization', [
            'languageCode' => 'ru',
            'formattingCode' => 'ru',
            'childLocalizations' => new ArrayCollection([$firstLevelRu])
        ]);
        $localizations = [
            'en' => ['en' => ['en' => []]],
            'ru' => ['ru' => []],
        ];
        return [
            ['entities' => [$en, $ru], 'localizations' => $localizations],
        ];
    }

    /**
     * @dataProvider getLocaleFallbacksCacheDataProvider
     *
     * @param array $localizations
     */
    public function testGetLocaleFallbacksCache(array $localizations)
    {
        $this->cache->expects($this->once())
            ->method('contains')
            ->with(LocalizationFallbackStrategy::CACHE_KEY)
            ->willReturn(true);
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(LocalizationFallbackStrategy::CACHE_KEY)
            ->willReturn($localizations);
        $this->assertEquals($localizations, $this->strategy->getLocaleFallbacks());
    }

    /**
     * @return array
     */
    public function getLocaleFallbacksCacheDataProvider()
    {
        return [
            [
                'localizations' => [
                    'en' => ['en_EN' => ['en_FR' => []]],
                    'ru' => ['ru_RU' => []],
                ]
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationFallbackStrategy::NAME, $this->strategy->getName());
    }

    public function testClearCache()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(LocalizationFallbackStrategy::CACHE_KEY);
        $this->strategy->clearCache();
    }
}
