<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TranslationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslationDomainProvider */
    private $domainProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DynamicTranslationMetadataCache */
    private $dbTranslationMetadataCache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager */
    private $objectManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslationKeyRepository */
    private $translationKeyRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslationRepository */
    private $translationRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LanguageRepository */
    private $languageRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var TranslationManager */
    private $translationManager;

    protected function setUp(): void
    {
        $this->domainProvider = $this->createMock(TranslationDomainProvider::class);
        $this->dbTranslationMetadataCache = $this->createMock(DynamicTranslationMetadataCache::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->translationKeyRepository = $this->createMock(TranslationKeyRepository::class);
        $this->translationRepository = $this->createMock(TranslationRepository::class);
        $this->languageRepository = $this->createMock(LanguageRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [TranslationKey::class, $this->translationKeyRepository],
                [Translation::class, $this->translationRepository],
                [Language::class, $this->languageRepository],
            ]);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->objectManager);

        $this->translationManager = new TranslationManager(
            $registry,
            $this->domainProvider,
            $this->dbTranslationMetadataCache,
            $this->eventDispatcher
        );
    }

    private function createTranslation(
        string $key,
        ?string $value,
        string $locale,
        string $domain,
        int $id = null
    ): Translation {
        $translationKey = new TranslationKey();
        $translationKey->setKey($key)->setDomain($domain);

        $language = new Language();
        $language->setCode($locale);

        $translation = new Translation();
        ReflectionUtil::setId($translation, $id);
        $translation->setTranslationKey($translationKey);
        $translation->setValue($value);
        $translation->setLanguage($language);

        return $translation;
    }

    public function testFindTranslationKey()
    {
        $key = 'key';
        $domain = TranslationManager::DEFAULT_DOMAIN;

        $this->objectManager->expects($this->never())
            ->method('persist');
        $this->objectManager->expects($this->never())
            ->method('flush');

        $this->translationKeyRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key, 'domain' => $domain]);

        $translationKey1 = $this->translationManager->findTranslationKey($key, $domain);
        $translationKey2 = $this->translationManager->findTranslationKey($key, $domain);

        $this->assertInstanceOf(TranslationKey::class, $translationKey1);
        $this->assertSame($translationKey1, $translationKey2);
    }

    public function testSaveTranslation()
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

        $this->objectManager->expects($this->never())
            ->method('persist');
        $this->objectManager->expects($this->never())
            ->method('flush');

        $translation1 = $this->translationManager->saveTranslation($key, $value, $locale, $domain);
        $translation2 = $this->translationManager->saveTranslation($key, $value, $locale, $domain);

        $this->assertInstanceOf(Translation::class, $translation1);
        $this->assertSame($translation1, $translation2);
    }

    public function testCreateTranslation()
    {
        $key = 'key';
        $value = 'value';
        $domain = TranslationManager::DEFAULT_DOMAIN;
        $locale = 'locale';
        $expectedTranslation = $this->createTranslation($key, $value, $locale, $domain);

        $this->translationKeyRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key, 'domain' => $domain])
            ->willReturn((new TranslationKey())->setKey($key)->setDomain($domain));

        $this->languageRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => $locale])
            ->willReturn((new Language())->setCode($locale));

        $this->objectManager->expects($this->never())
            ->method('persist');
        $this->objectManager->expects($this->never())
            ->method('flush');

        $translation = $this->translationManager->createTranslation($key, $value, $locale, $domain);

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertEquals($expectedTranslation, $translation);
    }

    public function testFlush()
    {
        $translationKey = (new TranslationKey())->setKey('key')->setDomain('domain');

        $this->translationKeyRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($translationKey);

        $this->languageRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn((new Language())->setCode('locale'));

        $translation = $this->translationManager->saveTranslation('key', 'value', 'locale', 'domain');

        $this->objectManager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$this->identicalTo($translationKey)],
                [$this->identicalTo($translation)]
            );
        $this->objectManager->expects($this->once())
            ->method('flush')
            ->with([$translationKey, $translation]);

        $this->translationManager->flush();
    }

    public function testFlushTranslationWithoutValue()
    {
        $key = 'test.key';
        $value = null;
        $locale = 'test_locale';
        $domain = 'test_domain';

        $translation = $this->createTranslation($key, $value, $locale, $domain, 42);

        $this->translationKeyRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($translation->getTranslationKey());

        $this->translationRepository->expects($this->any())
            ->method('findTranslation')
            ->willReturn($translation);

        $this->objectManager->expects($this->once())
            ->method('persist')
            ->with($translation->getTranslationKey());
        $this->objectManager->expects($this->once())
            ->method('remove')
            ->with($translation);
        $this->objectManager->expects($this->once())
            ->method('flush')
            ->with([$translation->getTranslationKey(), $translation]);

        $this->assertNull($this->translationManager->saveTranslation($key, $value, $locale, $domain));

        $this->translationManager->flush();
    }

    public function testFlushWithoutChanges()
    {
        $this->objectManager->expects($this->never())
            ->method($this->anything());

        $this->translationManager->flush();
    }

    public function testForceFlush()
    {
        $this->objectManager->expects($this->once())
            ->method('flush');

        $this->translationManager->flush(true);
    }

    public function testClear()
    {
        $this->translationRepository->expects($this->any())
            ->method('findTranslation')
            ->willReturn(new Translation());
        $this->domainProvider->expects($this->exactly(2))
            ->method('clearCache');

        $this->translationManager->saveTranslation('key', 'value', 'locale', 'domain');
        $this->translationManager->clear();

        $this->objectManager->expects($this->never())
            ->method('persist');
        $this->objectManager->expects($this->never())
            ->method('flush');

        $this->translationManager->flush();
    }

    /**
     * @dataProvider invalidateCacheDataProvider
     */
    public function testInvalidateCache(?string $with)
    {
        $this->dbTranslationMetadataCache->expects($this->once())
            ->method('updateTimestamp')
            ->with($with);

        $this->translationManager->invalidateCache($with);
    }

    public function invalidateCacheDataProvider(): array
    {
        return [
            [null],
            ['en'],
        ];
    }

    public function testRemoveMissingTranslationKey()
    {
        $nonExistingKey = uniqid('KEY_', true);
        $nonExistingDomain = uniqid('KEY_', true);
        $this->translationKeyRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null)
            ->with(['key' => $nonExistingKey, 'domain' => $nonExistingDomain]);
        $this->objectManager->expects($this->never())
            ->method('remove');

        $this->translationManager->removeTranslationKey($nonExistingKey, $nonExistingDomain);
    }
}
