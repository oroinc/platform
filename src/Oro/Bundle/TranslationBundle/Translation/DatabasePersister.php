<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class DatabasePersister
{
    /** @var DynamicTranslationMetadataCache */
    private $metadataCache;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var NativeQueryExecutorHelper */
    private $nativeQueryExecutorHelper;

    /**
     * @param ManagerRegistry                 $doctrine
     * @param NativeQueryExecutorHelper       $nativeQueryExecutorHelper
     * @param DynamicTranslationMetadataCache $metadataCache
     */
    public function __construct(
        ManagerRegistry $doctrine,
        NativeQueryExecutorHelper $nativeQueryExecutorHelper,
        DynamicTranslationMetadataCache $metadataCache
    ) {
        $this->doctrine = $doctrine;
        $this->nativeQueryExecutorHelper = $nativeQueryExecutorHelper;
        $this->metadataCache = $metadataCache;
    }

    /**
     * Persists data into DB in single transaction
     *
     * @param string $locale
     * @param array  $data translations strings, format same as MassageCatalog::all() returns
     *
     * @throws \Exception
     */
    public function persist($locale, array $data)
    {
        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();
        $writeCount = 0;

        try {
            $connection->beginTransaction();
            $translationsTableName = $this->nativeQueryExecutorHelper->getTableName(Translation::ENTITY_NAME);

            foreach ($data as $domain => $domainData) {
                $fetchStatement = 'SELECT id, `key`, `value` FROM ' . $translationsTableName .
                    ' WHERE locale = :locale' .
                    ' AND domain = :domain' .
                    ' AND scope = :scope';
                $existings = $connection->fetchAll(
                    $fetchStatement,
                    [
                        'locale' => $locale,
                        'domain' => $domain,
                        'scope'  => Translation::SCOPE_SYSTEM
                    ],
                    [
                        Type::STRING,
                        Type::STRING,
                        Type::STRING
                    ]
                );

                $existingTranslationKeys = array_column($existings, 'id', 'key');
                $existingTranslationValues = array_column($existings, 'value', 'key');

                foreach ($domainData as $key => $translation) {
                    if (strlen($key) > MySqlPlatform::LENGTH_LIMIT_TINYTEXT) {
                        continue;
                    }

                    $existingTranslationKey = array_key_exists($key, $existingTranslationKeys);
                    if (!$existingTranslationKey) {
                        $connection->insert(
                            $translationsTableName,
                            [
                                $connection->quoteIdentifier('key')   => $key,
                                $connection->quoteIdentifier('value') => $translation,
                                'locale'                              => $locale,
                                'domain'                              => $domain,
                                'scope'                               => Translation::SCOPE_SYSTEM
                            ],
                            [
                                Type::STRING,
                                Type::STRING,
                                Type::STRING,
                                Type::STRING,
                                Type::SMALLINT,
                            ]
                        );
                    } elseif ($existingTranslationKey && $existingTranslationValues[$key] !== $translation) {
                        $connection->update(
                            $translationsTableName,
                            [$connection->quoteIdentifier('value') => $translation],
                            ['id' => $existingTranslationKeys[$key]]
                        );
                    } else {
                        continue;
                    }

                    $writeCount++;
                }
            }

            if ($writeCount) {
                $connection->commit();
            }
        } catch (\Exception $exception) {
            $connection->rollBack();

            throw $exception;
        }

        // update timestamp in case when persist succeed
        $this->metadataCache->updateTimestamp($locale);
    }
}
