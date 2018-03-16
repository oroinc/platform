<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Exception\LanguageNotFoundException;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class DatabasePersister
{
    const BATCH_INSERT_ROWS_COUNT = 50;

    /** @var Registry */
    private $registry;

    /** @var TranslationManager */
    private $translationManager;

    /**
     * @param Registry $registry
     * @param TranslationManager $translationManager
     */
    public function __construct(Registry $registry, TranslationManager $translationManager)
    {
        $this->registry = $registry;
        $this->translationManager = $translationManager;
    }

    /**
     * Persists data into DB in single transaction
     *
     * @param string $locale
     * @param array $catalogData translations strings, format same as MassageCatalog::all() returns
     *
     * @param int $scope
     * @throws \Exception
     */
    public function persist($locale, array $catalogData, $scope = Translation::SCOPE_INSTALLED)
    {
        $em = $this->getEntityManager(Translation::class);

        $em->beginTransaction();
        try {
            $languageRepository = $this->getEntityRepository(Language::class);
            /** @var TranslationRepository $translationRepository */
            $translationRepository = $this->getEntityRepository(Translation::class);
            $connection = $this->getConnection(Translation::class);

            /** @var Language $language */
            $language = $languageRepository->findOneBy(['code' => $locale]);
            if (!$language) {
                throw new LanguageNotFoundException($locale);
            }

            $translationKeys = $this->processTranslationKeys($catalogData);
            $translations = $translationRepository->getTranslationsData($language->getId());
            $sqlData = [];
            foreach ($catalogData as $domain => $messages) {
                foreach ($messages as $key => $value) {
                    if (!isset($translationKeys[$domain][$key])) {
                        continue;
                    }
                    if (isset($translations[$translationKeys[$domain][$key]])) {
                        $this->updateTranslation(
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
                            $this->executeBatchTranslationInsert($sqlData);
                            $sqlData = [];
                        }
                    }
                }
            }
            $this->executeBatchTranslationInsert($sqlData);
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

    /**
     * @param string $class
     * @return EntityManager
     */
    protected function getEntityManager($class)
    {
        return $this->registry->getManagerForClass($class);
    }

    /**
     * @param string $class
     * @return Connection
     */
    protected function getConnection($class)
    {
        return $this->getEntityManager($class)->getConnection();
    }

    /**
     * @param string $class
     *
     * @return EntityRepository
     */
    protected function getEntityRepository($class)
    {
        return $this->getEntityManager($class)->getRepository($class);
    }


    /**
     * Loads translation keys to DB if needed
     *
     * @param array $domains
     *
     * @return array
     */
    private function processTranslationKeys(array $domains)
    {
        $connection = $this->getConnection(TranslationKey::class);
        /** @var TranslationKeyRepository $translationKeyRepository */
        $translationKeyRepository = $this->getEntityRepository(TranslationKey::class);

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
                if (strlen($key) > MySqlPlatform::LENGTH_LIMIT_TINYTEXT) {
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

    /**
     * @param array $sqlData

     * @return array
     */
    private function executeBatchTranslationInsert(array $sqlData)
    {
        $sql = 'INSERT INTO oro_translation (translation_key_id, language_id, value, scope) VALUES ';
        if (0 !== count($sqlData)) {
            $this->getConnection(Translation::class)->executeQuery($sql . implode(', ', $sqlData));
        }
    }

    /**
     * Update translation record in DB only if record is changed and scope in DB for this record is System
     *
     * @param string $value
     * @param int $languageId
     * @param array $translationDataItem
     * @param int $scope
     *
     * @return array
     */
    private function updateTranslation($value, $languageId, array $translationDataItem, $scope)
    {
        if ($translationDataItem['scope'] <= $scope && $translationDataItem['value'] !== $value) {
            $this->getConnection(Translation::class)->update(
                'oro_translation',
                ['value' => $value],
                [
                    'translation_key_id' => $translationDataItem['translation_key_id'],
                    'language_id' => $languageId
                ]
            );
        }
    }
}
