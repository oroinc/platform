<?php
namespace Oro\Bundle\TranslationBundle\Tests\Unit\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Registry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LanguageProvider */
    protected $languageProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DynamicTranslationMetadataCache */
    protected $dbTranslationMetadataCache;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Translator */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|JsTranslationDumper */
    protected $jsTranslationDumper;

    /** @var string */
    protected $translationCacheDir = '/translations';

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    protected $objectManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository */
    protected $objectRepository;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->languageProvider = $this->getMockBuilder(LanguageProvider::class)
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

        $this->objectRepository = $this->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->objectRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->objectManager);

        $this->manager = new TranslationManager(
            $this->registry,
            $this->languageProvider,
            $this->dbTranslationMetadataCache,
            $this->translator,
            $this->jsTranslationDumper,
            $this->translationCacheDir
        );
    }

    protected function tearDown()
    {
        unset(
            $this->registry,
            $this->languageProvider,
            $this->dbTranslationMetadataCache,
            $this->translator,
            $this->jsTranslationDumper,
            $this->objectManager,
            $this->objectRepository
        );
    }

    public function testFindTranslationKey()
    {
        $key = 'key';
        $domain = TranslationManager::DEFAULT_DOMAIN;

        $this->objectManager->expects($this->once())->method('persist');
        $this->objectManager->expects($this->never())->method('flush');

        $this->objectRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key, 'domain' => $domain]);

        $translationKey1 = $this->manager->findTranslationKey($key, $domain);
        $translationKey2 = $this->manager->findTranslationKey($key, $domain);

        $this->assertInstanceOf(TranslationKey::class, $translationKey1);
        $this->assertSame($translationKey1, $translationKey2);
    }

    public function testGetLanguageByCode()
    {
        $locale = 'en';

        $this->objectManager->expects($this->never())->method('persist');
        $this->objectManager->expects($this->never())->method('flush');

        $this->objectRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => $locale])
            ->willReturn((new Language())->setCode($locale));

        $language1 = $this->manager->getLanguageByCode($locale);
        $language2 = $this->manager->getLanguageByCode($locale);

        $this->assertInstanceOf(Language::class, $language1);
        $this->assertSame($language1, $language2);
    }

    /**
     * @dataProvider createValueDataProvider
     *
     * @param bool $persist
     */
    public function testCreateValue($persist = false)
    {
        $key = 'key';
        $value = 'value';
        $domain = TranslationManager::DEFAULT_DOMAIN;
        $locale = 'locale';

        $this->objectRepository->expects($this->at(0))
            ->method('findOneBy')
            ->with(['key' => $key, 'domain' => $domain])
            ->willReturn((new TranslationKey())->setKey($key)->setDomain($domain));

        $this->objectRepository->expects($this->at(1))
            ->method('findOneBy')
            ->with(['code' => $locale])
            ->willReturn((new Language())->setCode($locale));

        $this->objectManager->expects($persist ? $this->atLeastOnce() : $this->never())->method('persist');

        $translation1 = $this->manager->createValue($key, $value, $locale, $domain, $persist);
        $translation2 = $this->manager->createValue($key, $value, $locale, $domain, $persist);

        $this->assertInstanceOf(Translation::class, $translation1);
        $this->assertSame($translation1, $translation2);
    }

    public function createValueDataProvider()
    {
        return [
            ['persist' => true],
            ['without_persist' => false]
        ];
    }

    public function testFlush()
    {
        $this->objectManager->expects($this->once())->method('flush');
        $this->manager->flush();
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

    public function invalidateCacheDataProvider()
    {
        return [
            [null],
            ['en'],
        ];
    }

    public function testRebuildCache()
    {
        $this->translator->expects($this->once())
            ->method('rebuildCache');

        $this->manager->rebuildCache();
    }
}
