<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Registry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DynamicTranslationMetadataCache */
    protected $dbTranslationMetadataCache;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Translator */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|JsTranslationDumper */
    protected $jsTranslationDumper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    protected $objectManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslationKeyRepository */
    protected $translationKeyRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LanguageRepository */
    protected $languageRepository;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dbTranslationMetadataCache = $this->getMockBuilder(DynamicTranslationMetadataCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsTranslationDumper = $this->getMockBuilder(JsTranslationDumper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationKeyRepository = $this->getMockBuilder(TranslationKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->languageRepository = $this->getMockBuilder(LanguageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap([
                [TranslationKey::class, $this->translationKeyRepository],
                [Language::class, $this->languageRepository],
            ]));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->objectManager);

        $this->manager = new TranslationManager(
            $this->registry,
            $this->dbTranslationMetadataCache,
            $this->translator,
            $this->jsTranslationDumper
        );
    }

    protected function tearDown()
    {
        unset(
            $this->registry,
            $this->dbTranslationMetadataCache,
            $this->translator,
            $this->jsTranslationDumper,
            $this->objectManager,
            $this->translationKeyRepository,
            $this->languageRepository
        );
    }

    public function testFindTranslationKey()
    {
        $key = 'key';
        $domain = TranslationManager::DEFAULT_DOMAIN;

        $this->objectManager->expects($this->once())->method('persist');
        $this->objectManager->expects($this->never())->method('flush');

        $this->translationKeyRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key, 'domain' => $domain]);

        $translationKey1 = $this->manager->findTranslationKey($key, $domain);
        $translationKey2 = $this->manager->findTranslationKey($key, $domain);

        $this->assertInstanceOf(TranslationKey::class, $translationKey1);
        $this->assertSame($translationKey1, $translationKey2);
    }

    /**
     * @dataProvider createTranslationDataProvider
     *
     * @param bool $persist
     */
    public function testCreateTranslation($persist = false)
    {
        $key = 'key';
        $value = 'value';
        $domain = TranslationManager::DEFAULT_DOMAIN;
        $locale = 'locale';

        $this->translationKeyRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key, 'domain' => $domain])
            ->willReturn((new TranslationKey())->setKey($key)->setDomain($domain));

        $this->languageRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => $locale])
            ->willReturn((new Language())->setCode($locale));

        $this->objectManager->expects($persist ? $this->atLeastOnce() : $this->never())->method('persist');

        $translation1 = $this->manager->createTranslation($key, $value, $locale, $domain, $persist);
        $translation2 = $this->manager->createTranslation($key, $value, $locale, $domain, $persist);

        $this->assertInstanceOf(Translation::class, $translation1);
        $this->assertSame($translation1, $translation2);
    }

    /**
     * @return array
     */
    public function createTranslationDataProvider()
    {
        return [
            ['persist' => true],
            ['without_persist' => false]
        ];
    }

    public function testFlush()
    {
        $this->translationKeyRepository->expects($this->any())
            ->method('findOneBy')->willReturn((new TranslationKey())->setKey('key')->setDomain('domain'));

        $this->languageRepository->expects($this->any())
            ->method('findOneBy')->willReturn((new Language())->setCode('locale'));

        $translation = $this->manager->createTranslation('key', 'value', 'locale', 'domain');

        $this->objectManager->expects($this->once())->method('flush')->with([$translation]);
        $this->manager->flush();
    }

    public function testFlushWithoutChanges()
    {
        $this->objectManager->expects($this->never())->method('flush');
        $this->manager->flush();
    }

    public function testForceFlush()
    {
        $this->objectManager->expects($this->once())->method('flush');
        $this->manager->flush(true);
    }

    public function testClear()
    {
        $this->objectManager->expects($this->once())->method('clear');
        $this->manager->clear();
    }

    /**
     * @dataProvider invalidateCacheDataProvider
     *
     * @param $with
     */
    public function testInvalidateCache($with)
    {
        $this->dbTranslationMetadataCache->expects($this->once())->method('updateTimestamp')->with($with);
        $this->manager->invalidateCache($with);
    }

    /**
     * @return array
     */
    public function invalidateCacheDataProvider()
    {
        return [
            [null],
            ['en'],
        ];
    }
}
