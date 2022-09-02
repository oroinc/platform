<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Async\Topic\DumpJsTranslationsTopic;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TranslationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationDomainProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $domainProvider;

    /** @var DynamicTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    private $dynamicTranslationCache;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var TranslationKeyRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $translationKeyRepository;

    /** @var TranslationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $translationRepository;

    /** @var LanguageRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $languageRepository;

    /** @var TranslationManager */
    private $translationManager;

    protected function setUp(): void
    {
        $this->domainProvider = $this->createMock(TranslationDomainProvider::class);
        $this->dynamicTranslationCache = $this->createMock(DynamicTranslationCache::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->translationKeyRepository = $this->createMock(TranslationKeyRepository::class);
        $this->translationRepository = $this->createMock(TranslationRepository::class);
        $this->languageRepository = $this->createMock(LanguageRepository::class);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [TranslationKey::class, $this->translationKeyRepository],
                [Translation::class, $this->translationRepository],
                [Language::class, $this->languageRepository],
            ]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->translationManager = new TranslationManager(
            $doctrine,
            $this->domainProvider,
            $this->dynamicTranslationCache,
            $this->producer,
            ['validators', 'jsmessages']
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

        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->translationKeyRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['key' => $key, 'domain' => $domain]);

        $translationKey1 = $this->translationManager->findTranslationKey($key, $domain);
        $translationKey2 = $this->translationManager->findTranslationKey($key, $domain);

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

        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
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

        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
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

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$this->identicalTo($translationKey)],
                [$this->identicalTo($translation)]
            );
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->with([$translationKey, $translation]);

        $this->dynamicTranslationCache->expects($this->once())
            ->method('delete')
            ->with(['locale']);
        $this->producer->expects($this->never())
            ->method('send');

        $this->translationManager->flush();
    }

    public function testFlushWhenJsTranslationsChanged()
    {
        $translationKey = (new TranslationKey())->setKey('key')->setDomain('jsmessages');

        $this->translationKeyRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($translationKey);

        $this->languageRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn((new Language())->setCode('locale'));

        $translation = $this->translationManager->saveTranslation('key', 'value', 'locale', 'jsmessages');

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$this->identicalTo($translationKey)],
                [$this->identicalTo($translation)]
            );
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->with([$translationKey, $translation]);

        $this->dynamicTranslationCache->expects($this->once())
            ->method('delete')
            ->with(['locale']);
        $this->producer->expects($this->once())
            ->method('send')
            ->with(DumpJsTranslationsTopic::getName(), []);

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

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($translation->getTranslationKey());
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($translation);
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->with([$translation->getTranslationKey(), $translation]);

        $this->assertNull($this->translationManager->saveTranslation($key, $value, $locale, $domain));

        $this->dynamicTranslationCache->expects($this->once())
            ->method('delete')
            ->with([$locale]);

        $this->translationManager->flush();
    }

    public function testFlushWithoutChanges()
    {
        $this->entityManager->expects($this->never())
            ->method($this->anything());
        $this->domainProvider->expects($this->once())
            ->method('clearCache');
        $this->dynamicTranslationCache->expects($this->never())
            ->method('delete');

        $this->translationManager->flush();
    }

    public function testForceFlush()
    {
        $this->entityManager->expects($this->once())
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

        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->translationManager->flush();
    }

    public function testInvalidateCache()
    {
        $locale = 'en';

        $this->dynamicTranslationCache->expects($this->once())
            ->method('delete')
            ->with([$locale]);

        $this->translationManager->invalidateCache($locale);
    }

    public function testRemoveMissingTranslationKey()
    {
        $nonExistingKey = uniqid('KEY_', true);
        $nonExistingDomain = uniqid('KEY_', true);
        $this->translationKeyRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null)
            ->with(['key' => $nonExistingKey, 'domain' => $nonExistingDomain]);
        $this->entityManager->expects($this->never())
            ->method('remove');

        $this->translationManager->removeTranslationKey($nonExistingKey, $nonExistingDomain);
    }
}
