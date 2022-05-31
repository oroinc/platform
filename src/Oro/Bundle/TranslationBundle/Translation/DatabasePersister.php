<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Exception\LanguageNotFoundException;
use Oro\Bundle\TranslationBundle\Helper\FileBasedLanguageHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

/**
 * Persists translations strings into DB in single transaction.
 */
class DatabasePersister
{
    private const BATCH_INSERT_ROWS_COUNT = 50;

    private ManagerRegistry $doctrine;
    private TranslationManager $translationManager;
    private FileBasedLanguageHelper $fileBasedLanguageHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        TranslationManager $translationManager,
        FileBasedLanguageHelper $fileBasedLanguageHelper
    ) {
        $this->doctrine = $doctrine;
        $this->translationManager = $translationManager;
        $this->fileBasedLanguageHelper = $fileBasedLanguageHelper;
    }

    /**
     * Persists data into DB in single transaction
     *
     * @param string $locale
     * @param array $catalogData translations strings, format same as MassageCatalog::all() returns
     * @param int $scope
     */
    public function persist(string $locale, array $catalogData, int $scope = Translation::SCOPE_INSTALLED): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Translation::class);
        $em->beginTransaction();
        try {
            $connection = $em->getConnection();

            $language = $this->getLanguageForLocale($locale);
            $this->validateAndUpdateFileBasedLanguage($em, $language, $scope);

            $translationKeys = $this->processTranslationKeys($connection, $catalogData);
            $translations = $em->getRepository(Translation::class)->getTranslationsData($language->getId());
            $sqlData = [];
            foreach ($catalogData as $domain => $messages) {
                foreach ($messages as $key => $value) {
                    if (!isset($translationKeys[$domain][$key])) {
                        continue;
                    }
                    if (isset($translations[$translationKeys[$domain][$key]])) {
                        $this->updateTranslation(
                            $connection,
                            $value,
                            $language->getId(),
                            $translations[$translationKeys[$domain][$key]],
                            $scope
                        );
                    } else {
                        $sqlData[] = sprintf(
                            '(%d, %d, %s, %d)',
                            $translationKeys[$domain][$key],
                            $language->getId(),
                            $connection->quote($value),
                            $scope
                        );
                        if (self::BATCH_INSERT_ROWS_COUNT === count($sqlData)) {
                            $this->executeBatchTranslationInsert($connection, $sqlData);
                            $sqlData = [];
                        }
                    }
                }
            }
            $this->executeBatchTranslationInsert($connection, $sqlData);
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();

            throw $exception;
        }

        // update timestamp in case when persist succeed
        $this->translationManager->invalidateCache($locale);
        // Invalidate other caches
        $this->translationManager->clear();
    }

    private function getLanguageForLocale(string $locale): Language
    {
        /** @var Language $language */
        $language = $this->doctrine->getRepository(Language::class)->findOneBy(['code' => $locale]);
        if (!$language) {
            throw new LanguageNotFoundException($locale);
        }

        return $language;
    }

    private function validateAndUpdateFileBasedLanguage(
        EntityManagerInterface $em,
        Language $language,
        int $scope = Translation::SCOPE_INSTALLED
    ): void {
        if ($scope === Translation::SCOPE_SYSTEM
            && !$language->isLocalFilesLanguage()
            && $this->fileBasedLanguageHelper->isFileBasedLocale($language->getCode())
        ) {
            $language->setLocalFilesLanguage(true);
            $em->persist($language);
            $em->flush();
        }
    }

    /**
     * Loads translation keys to DB if needed
     */
    private function processTranslationKeys(Connection $connection, array $domains): array
    {
        /** @var TranslationKeyRepository $translationKeyRepository */
        $translationKeyRepository = $this->doctrine->getRepository(TranslationKey::class);

        $translationKeys = $translationKeyRepository->getTranslationKeysData();
        $sql = sprintf(
            'INSERT INTO oro_translation_key (%s, %s) VALUES ',
            $connection->quoteIdentifier('domain'),
            $connection->quoteIdentifier('key')
        );
        $sqlData = [];
        $needUpdate = false;
        foreach ($domains as $domain => $messages) {
            foreach ($messages as $key => $value) {
                if (\strlen($key) > MySqlPlatform::LENGTH_LIMIT_TINYTEXT) {
                    continue;
                }
                if (!isset($translationKeys[$domain][$key])) {
                    $sqlData[] = sprintf('(%s, %s)', $connection->quote($domain), $connection->quote($key));
                    $translationKeys[$domain][$key] = 1;
                    $needUpdate = true;

                    if (self::BATCH_INSERT_ROWS_COUNT === count($sqlData)) {
                        $connection->executeQuery($sql . implode(', ', $sqlData));
                        $sqlData = [];
                    }
                }
            }
        }
        if (0 !== count($sqlData)) {
            $connection->executeQuery($sql . implode(', ', $sqlData));
        }
        if ($needUpdate) {
            $translationKeys = $translationKeyRepository->getTranslationKeysData();
        }

        return $translationKeys;
    }

    private function executeBatchTranslationInsert(Connection $connection, array $sqlData): void
    {
        if (0 !== count($sqlData)) {
            $connection->executeQuery(
                'INSERT INTO oro_translation (translation_key_id, language_id, value, scope) VALUES '
                . implode(', ', $sqlData)
            );
        }
    }

    /**
     * Update translation record in DB:
     *  - set the new translation value if record is changed and scope in DB for this record is System
     *  - change the scope of the record to System if the existing DB value and the value from the file are the same
     *    and the scope of the file record is System
     */
    private function updateTranslation(
        Connection $connection,
        mixed $value,
        int $languageId,
        array $translationDataItem,
        int $scope
    ): void {
        if ($translationDataItem['scope'] <= $scope && $translationDataItem['value'] !== $value) {
            $connection->update(
                'oro_translation',
                ['value' => $value],
                [
                    'translation_key_id' => $translationDataItem['translation_key_id'],
                    'language_id' => $languageId
                ]
            );
        } elseif ($translationDataItem['scope'] !== Translation::SCOPE_SYSTEM
            && $scope === Translation::SCOPE_SYSTEM
            && $translationDataItem['value'] === $value
        ) {
            $connection->update(
                'oro_translation',
                ['scope' => Translation::SCOPE_SYSTEM],
                [
                    'translation_key_id' => $translationDataItem['translation_key_id'],
                    'language_id' => $languageId
                ]
            );
        }
    }
}
