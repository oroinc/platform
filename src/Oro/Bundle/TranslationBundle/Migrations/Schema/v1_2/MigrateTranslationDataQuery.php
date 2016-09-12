<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class MigrateTranslationDataQuery extends ParametrizedMigrationQuery
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
        $keyField = $this->connection->getDatabasePlatform() instanceof MySqlPlatform ? '`key`' : 'key';

        $queries = [
            [
                'INSERT INTO oro_language (code, created_at, updated_at) '
                    . 'SELECT DISTINCT locale, now(), now() FROM oro_translation',
                [],
                []
            ],
            [
                'UPDATE oro_translation t SET language_id = (SELECT id FROM oro_language l WHERE l.code = t.locale)',
                [],
                []
            ],
            [
                'INSERT INTO oro_translation_key (' . $keyField . ', domain) '
                    . 'SELECT DISTINCT t.key, t.domain FROM oro_translation t',
                [],
                []
            ],
            [
                'UPDATE oro_translation t SET key_id = '
                    . '(SELECT id FROM oro_translation_key k WHERE k.key = t.key AND k.domain = t.domain)',
                [],
                []
            ],
        ];

        foreach ($this->getDuplicatedKeys($logger) as $id) {
            $queries[] = [
                'DELETE FROM oro_translation WHERE id = :id',
                ['id' => $id],
                ['id' => Type::INTEGER]
            ];
        }

        foreach ($queries as $query) {
            $this->logQuery($logger, $query[0], $query[1], $query[2]);
            if (!$dryRun) {
                $this->connection->executeUpdate($query[0], $query[1], $query[2]);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    private function getDuplicatedKeys(LoggerInterface $logger)
    {
        $query = [
            'SELECT t.id, t.key, t.scope FROM oro_translation t WHERE ('
                . '     SELECT COUNT(st.id) FROM oro_translation st '
                . '     WHERE st.key = t.key AND st.locale = t.locale AND st.domain = t.domain'
                . ') > 1 '
                . 'ORDER BY t.key, t.scope DESC',
            [],
            [],
        ];

        $this->logQuery($logger, $query[0], $query[1], $query[2]);

        $items = $this->connection->fetchAll($query[0], $query[1], $query[2]);

        // filter duplications by max(scope)
        $filteredItems = array_filter($items, function ($item) use ($items) {
            foreach ($items as $subitem) {
                if ($item['key'] === $subitem['key'] && $item['id'] !== $subitem['id']) {
                    return $item['scope'] >= $subitem['scope'];
                }
            }

            return true;
        });

        // get a unique key values that will be left
        $keys = array_unique(array_column($filteredItems, 'key', 'id'));

        // exclude duplication keys
        $duplicatedItems = array_filter($items, function ($item) use ($keys) {
            return !isset($keys[$item['id']]);
        });

        return array_column($duplicatedItems, 'id');
    }
}
