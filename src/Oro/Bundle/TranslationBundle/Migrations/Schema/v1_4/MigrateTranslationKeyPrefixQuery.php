<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class MigrateTranslationKeyPrefixQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateData($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->migrateData($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function migrateData(LoggerInterface $logger, $dryRun = false)
    {
        $prefixes = ['orocrm', 'oropro'];
        $queries = [];
        foreach ($prefixes as $prefix) {
            $items = $this->getDuplicatesItems($logger, $prefix);
            foreach ($items as $item) {
                //Remove Old translations which have Newest translation
                $queries[] = $this->getRemoveExistingTranslationQuery($item['_from'], explode(',', $item['_langs']));

                //Set Latest Translation Key to Old Translation
                $queries[] = $this->getReplaceQuery($item['_from'], $item['_to']);

                //Remove Old Translation Key
                $queries[] = $this->getRemoveQuery($item['_from']);
            }
        }
        foreach ($queries as $query) {
            $this->logQuery($logger, $query);
            if (!$dryRun) {
                $this->connection->executeQuery($query);
            }
        }
    }

    /**
     * Should be retrieved all translation key ids for old and new key-forms
     * and all language ids for existing translation for new keys
     *
     * @param LoggerInterface $logger
     * @param string $prefix
     *
     * @return array
     */
    protected function getDuplicatesItems(LoggerInterface $logger, $prefix)
    {
        $query = 'SELECT GROUP_CONCAT(DISTINCT tk2.id) as _from, ' .
                'GROUP_CONCAT(DISTINCT tk1.id) as _to, ' .
                'GROUP_CONCAT(t1.language_id) as _langs ' .
                'FROM oro_translation t1 ' .
                'JOIN oro_translation_key tk1 ON t1.translation_key_id = tk1.id ' .
                sprintf(
                    ' JOIN oro_translation_key tk2 ON CONCAT(\'%s\', SUBSTR(tk1.`key`, 4)) = tk2.`key` ',
                    $prefix
                ) .
                'JOIN oro_translation t2 ON t2.translation_key_id = tk2.id AND tk1.domain = tk2.domain ' .
                'WHERE tk1.`key` LIKE  \'oro.%\' ' .
                'GROUP BY tk1.id, tk2.domain;';

        if ($this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $query = 'SELECT ARRAY_TO_STRING(ARRAY_AGG(DISTINCT tk2."id"), \',\') AS _from, ' .
                    'ARRAY_TO_STRING(ARRAY_AGG(DISTINCT tk1."id"), \',\') AS _to, ' .
                    'ARRAY_TO_STRING(ARRAY_AGG(DISTINCT t1.language_id),	  \',\') AS _langs ' .
                    'FROM oro_translation t1 ' .
                    'JOIN oro_translation_key tk1 ON t1.translation_key_id = tk1."id" ' .
                    sprintf(
                        'JOIN oro_translation_key tk2 ON CONCAT (\'%s\', SUBSTR(tk1."key", 4)) = tk2."key" ',
                        $prefix
                    ) .
                    'JOIN oro_translation t2 ON t2.translation_key_id = tk2."id" AND tk1."domain" = tk2."domain" ' .
                    'WHERE tk1."key" LIKE \'oro.%\' ' .
                    'GROUP BY tk1."id", tk2."domain";';
        }
        $this->logQuery($logger, $query);

        return $this->connection->fetchAll($query);
    }

    /**
     * @param string $from
     * @param array $langs
     *
     * @return string
     */
    private function getRemoveExistingTranslationQuery($from, array $langs)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete('oro_translation')
            ->andWhere($qb->expr()->eq('translation_key_id', $from))
            ->andWhere($qb->expr()->in('language_id', $langs));

        return $qb->getSQL();
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    private function getReplaceQuery($from, $to)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update('oro_translation')
            ->andWhere($qb->expr()->eq('translation_key_id', $from))
            ->set('translation_key_id', $to);

        return $qb->getSQL();
    }

    /**
     * @param string $id
     *
     * @return string
     */
    private function getRemoveQuery($id)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete('oro_translation_key')
            ->andWhere($qb->expr()->eq('id', $id));

        return $qb->getSQL();
    }
}
