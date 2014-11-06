<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateFileRelation extends ParametrizedMigrationQuery
{
    const ENTITY_PATH = 'Oro\\Bundle\\AttachmentBundle\\Entity\\File';

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
        $requireUpdate = [];
        $configs       = $this->getConfigs();

        foreach ($configs as $id => $config) {
            if (!empty($config['extend']['relation'])) {
                foreach ($config['extend']['relation'] as $k => $relation) {
                    if (!empty($relation['target_entity']) && self::ENTITY_PATH === $relation['target_entity']) {
                        $cascadeActions = !empty($relation['cascade']) ? $relation['cascade'] : [];

                        if (!in_array('persist', $cascadeActions, true)) {
                            $cascadeActions[]                            = 'persist';
                            $config['extend']['relation'][$k]['cascade'] = $cascadeActions;

                            $requireUpdate[] = ['data' => $config, 'id' => $id];
                        }
                    }
                }
            }
        }

        $query = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
        $types = ['id' => Type::INTEGER, 'data' => Type::TARRAY];

        foreach ($requireUpdate as $params) {
            $this->logQuery($logger, $query, $params, $types);

            if (!$dryRun) {
                $this->connection->executeQuery($query, $params, $types);
            }
        }
    }

    /**
     * @return array
     */
    protected function getConfigs()
    {
        $result = [];
        $sql    = 'SELECT id, class_name, data FROM oro_entity_config';

        $rows = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $result[$row['id']] = $this->connection->convertToPHPValue($row['data'], 'array');
        }

        return $result;
    }
}
