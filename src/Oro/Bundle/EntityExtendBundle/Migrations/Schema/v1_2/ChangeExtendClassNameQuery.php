<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_2;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class ChangeExtendClassNameQuery extends ParametrizedMigrationQuery
{
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
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $configs = $this->loadConfigs($logger);
        foreach ($configs as $id => $item) {
            $data = $item['data'];
            if (!isset($data['extend']['is_extend']) || !$data['extend']['is_extend']) {
                continue;
            }
            if (empty($data['extend']['extend_class']) || empty($data['extend']['schema']['parent'])) {
                continue;
            }

            $className = $item['class_name'];
            if (ExtendHelper::isCustomEntity($className)) {
                continue;
            }

            $parentClass    = $data['extend']['schema']['parent'];
            $oldExtendClass = $data['extend']['extend_class'];
            $newExtendClass = ExtendHelper::getExtendEntityProxyClassName($parentClass);
            if (!$newExtendClass || $newExtendClass === $oldExtendClass) {
                continue;
            }

            $data['extend']['extend_class'] = $newExtendClass;
            if (isset($data['extend']['schema']['entity'])) {
                $data['extend']['schema']['entity'] = $newExtendClass;
            }
            if (isset($data['extend']['schema']['doctrine'][$oldExtendClass])) {
                $data['extend']['schema']['doctrine'][$newExtendClass] =
                    $data['extend']['schema']['doctrine'][$oldExtendClass];
                unset($data['extend']['schema']['doctrine'][$oldExtendClass]);
            }

            $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
            $params = ['data' => $data, 'id' => $id];
            $types  = ['data' => 'array', 'id' => 'integer'];
            $logger->info(
                sprintf(
                    'Change extend class from "%s" to "%s" for "%s".',
                    $oldExtendClass,
                    $newExtendClass,
                    $className
                )
            );
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $params, $types);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function loadConfigs(LoggerInterface $logger)
    {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $result = [];

        $rows = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $result[$row['id']] = [
                'class_name' => $row['class_name'],
                'data'       => $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }

        return $result;
    }
}
