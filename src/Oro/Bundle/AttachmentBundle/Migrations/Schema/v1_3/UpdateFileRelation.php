<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateFileRelation extends ParametrizedMigrationQuery
{
    const ENTITY_PATH  = 'Oro\Bundle\AttachmentBundle\Entity\File';
    const UPDATE_QUERY = 'UPDATE oro_entity_config SET `data` = "%s" WHERE `id` = %d';
    const SELECT_QUERY = 'SELECT id, class_name, data FROM oro_entity_config';

    /** @var Schema */
    protected $schema;

    /**
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);
        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $entities            = $this->getConfigurableEntitiesData($logger);
        $updateConfigQueries = [];

        foreach ($entities as $configData) {
            $config = &$configData['data'];

            if (!empty($config['extend']['relation'])) {
                $relations = &$config['extend']['relation'];

                foreach ($relations as &$relation) {
                    if (!empty($relation['target_entity']) && self::ENTITY_PATH === $relation['target_entity']) {
                        if (empty($relation['cascade'])) {
                            $relation['cascade'] = ['persist'];
                            $updateConfigQueries[] = sprintf(
                                self::UPDATE_QUERY,
                                $this->connection->convertToDatabaseValue($config, 'array'),
                                $configData['id']
                            );
                        } elseif (!$this->hasPersist($relation)) {
                            array_push($relation['cascade'], 'persist');
                            $updateConfigQueries[] = sprintf(
                                self::UPDATE_QUERY,
                                $this->connection->convertToDatabaseValue($configData, 'array'),
                                $configData['id']
                            );
                        }
                    }
                }
            }
        }

        foreach ($updateConfigQueries as $query) {
            $this->logQuery($logger, $query);
            if (!$dryRun) {
                $this->connection->executeQuery($query);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getConfigurableEntitiesData(LoggerInterface $logger)
    {
        $this->logQuery($logger, self::SELECT_QUERY);
        $result = [];
        $rows   = $this->connection->fetchAll(self::SELECT_QUERY);

        foreach ($rows as $row) {
            $result[$row['class_name']] = [
                'id'   => $row['id'],
                'data' => $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }
        return $result;
    }

    /**
     * @param array $relation
     *
     * @return boolean
     */
    protected function hasPersist(array $relation)
    {
        return in_array('persist', array_flip($relation['cascade']));
    }
}
