<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Helper\FileBasedLanguageHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;
use Oro\Component\Testing\ReflectionUtil;

class DatabasePersisterTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_LOCALE = 'en';
    private const TEST_DATA = [
        'messages'   => [
            'key_1' => 'value_1',
            'key_2' => 'value_2',
            'key_3' => 'value_3',
        ],
        'validators' => [
            'key_1' => 'value_1',
            'key_2' => 'value_2',
        ]
    ];

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var TranslationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $translationRepository;

    /** @var TranslationKeyRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $translationKeyRepository;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $translationManager;

    /** @var FileBasedLanguageHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fileBasedLanguageHelper;

    /** @var Language */
    private $language;

    /** @var DatabasePersister */
    private $persister;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translationRepository = $this->createMock(TranslationRepository::class);
        $this->translationKeyRepository = $this->createMock(TranslationKeyRepository::class);
        $this->translationManager = $this->createMock(TranslationManager::class);
        $this->fileBasedLanguageHelper = $this->createMock(FileBasedLanguageHelper::class);

        $this->language = new Language();
        ReflectionUtil::setId($this->language, 1);
        $this->language->setCode(self::TEST_LOCALE);
        $this->language->setLocalFilesLanguage(false);

        $languageRepository = $this->createMock(LanguageRepository::class);
        $languageRepository->expects(self::any())
            ->method('findOneBy')
            ->willReturn($this->language);

        $connection = $this->createMock(Connection::class);

        $this->em->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);
        $this->em->expects(self::any())
            ->method('getRepository')
            ->with(Translation::class)
            ->willReturn($this->translationRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Translation::class)
            ->willReturn($this->em);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [TranslationKey::class, null, $this->translationKeyRepository],
                [Language::class, null, $languageRepository]
            ]);

        $this->persister = new DatabasePersister(
            $doctrine,
            $this->translationManager,
            $this->fileBasedLanguageHelper
        );
    }

    public function testPersist(): void
    {
        $this->em->expects(self::once())
            ->method('beginTransaction');
        $this->em->expects(self::once())
            ->method('commit');
        $this->em->expects(self::never())
            ->method('rollback');

        $this->translationRepository->expects(self::once())
            ->method('getTranslationsData')
            ->with($this->language->getId())
            ->willReturn([]);

        $this->translationKeyRepository->expects(self::exactly(2))
            ->method('getTranslationKeysData')
            ->willReturn([]);

        $this->translationManager->expects(self::once())
            ->method('invalidateCache')
            ->with(self::TEST_LOCALE);
        $this->translationManager->expects(self::once())
            ->method('clear');

        $this->fileBasedLanguageHelper->expects(self::once())
            ->method('isFileBasedLocale')
            ->with(self::TEST_LOCALE)
            ->willReturn(false);

        $this->em->expects(self::never())
            ->method('persist');
        $this->em->expects(self::never())
            ->method('flush');

        $this->persister->persist(self::TEST_LOCALE, self::TEST_DATA, Translation::SCOPE_SYSTEM);
    }

    public function testExceptionScenario(): void
    {
        $this->expectException(\LogicException::class);

        $this->em->expects(self::once())
            ->method('beginTransaction');
        $this->em->expects(self::once())
            ->method('commit')
            ->willThrowException(new \LogicException());
        $this->em->expects(self::once())
            ->method('rollback');

        $this->translationRepository->expects(self::once())
            ->method('getTranslationsData')
            ->with($this->language->getId())
            ->willReturn([]);

        $this->translationKeyRepository->expects(self::exactly(2))
            ->method('getTranslationKeysData')
            ->willReturn([]);

        $this->translationManager->expects(self::never())
            ->method('invalidateCache');
        $this->translationManager->expects(self::never())
            ->method('clear');

        $this->fileBasedLanguageHelper->expects(self::once())
            ->method('isFileBasedLocale')
            ->with(self::TEST_LOCALE)
            ->willReturn(false);

        $this->persister->persist(self::TEST_LOCALE, self::TEST_DATA, Translation::SCOPE_SYSTEM);
    }

    public function testPersistWithFilesBasedLocale(): void
    {
        $this->em->expects(self::once())
            ->method('beginTransaction');
        $this->em->expects(self::once())
            ->method('commit');
        $this->em->expects(self::never())
            ->method('rollback');

        $this->translationRepository->expects(self::once())
            ->method('getTranslationsData')
            ->with($this->language->getId())
            ->willReturn([]);

        $this->translationKeyRepository->expects(self::exactly(2))
            ->method('getTranslationKeysData')
            ->willReturn([]);

        $this->translationManager->expects(self::once())
            ->method('invalidateCache')
            ->with(self::TEST_LOCALE);
        $this->translationManager->expects(self::once())
            ->method('clear');

        $this->fileBasedLanguageHelper->expects(self::once())
            ->method('isFileBasedLocale')
            ->with(self::TEST_LOCALE)
            ->willReturn(true);

        $this->em->expects(self::once())
            ->method('persist')
            ->with($this->language);
        $this->em->expects(self::once())
            ->method('flush');

        $this->persister->persist(self::TEST_LOCALE, self::TEST_DATA, Translation::SCOPE_SYSTEM);

        self::assertTrue($this->language->isLocalFilesLanguage());
    }

    public function testPersistWithNonSystemScope(): void
    {
        $this->em->expects(self::once())
            ->method('beginTransaction');
        $this->em->expects(self::once())
            ->method('commit');
        $this->em->expects(self::never())
            ->method('rollback');

        $this->translationRepository->expects(self::once())
            ->method('getTranslationsData')
            ->with($this->language->getId())
            ->willReturn([]);

        $this->translationKeyRepository->expects(self::exactly(2))
            ->method('getTranslationKeysData')
            ->willReturn([]);

        $this->translationManager->expects(self::once())
            ->method('invalidateCache')
            ->with(self::TEST_LOCALE);
        $this->translationManager->expects(self::once())
            ->method('clear');

        $this->fileBasedLanguageHelper->expects(self::never())
            ->method('isFileBasedLocale');

        $this->em->expects(self::never())
            ->method('persist');
        $this->em->expects(self::never())
            ->method('flush');

        $this->persister->persist(self::TEST_LOCALE, self::TEST_DATA);

        self::assertFalse($this->language->isLocalFilesLanguage());
    }
}
