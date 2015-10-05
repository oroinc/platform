<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;

class FixAssociationLabelsQuery extends ParametrizedMigrationQuery
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $entityLabels = $this->loadEntityLabels($logger);
        $fieldConfigs = $this->loadAssociationFieldConfigs($logger);
        foreach ($fieldConfigs as $config) {
            $data = $config['data'];
            if (empty($data['extend']['target_entity'])) {
                continue;
            }

            $className       = $config['class'];
            $fieldName       = $config['field'];
            $fieldType       = $config['type'];
            $defaultLabel    = ConfigHelper::getTranslationKey('entity', 'label', $className, $fieldName);
            $targetClassName = $data['extend']['target_entity'];
            $label           = $data['entity']['label'];

            $hasChanges = false;
            if ($label !== $defaultLabel && isset($entityLabels[$targetClassName])) {
                if ($fieldType === RelationType::MANY_TO_ONE) {
                    $targetEntityLabel = $entityLabels[$targetClassName]['label'];
                    if ($targetEntityLabel && $label !== $targetEntityLabel && false === strpos($label, '.')) {
                        $data['entity']['label'] = $targetEntityLabel;
                        $hasChanges              = true;
                    }
                } elseif ($fieldType === RelationType::MANY_TO_MANY) {
                    $targetEntityLabel       = $entityLabels[$targetClassName]['label'];
                    $targetEntityPluralLabel = $entityLabels[$targetClassName]['plural_label'];
                    if ($targetEntityLabel
                        && $targetEntityPluralLabel
                        && $label !== $targetEntityPluralLabel
                        && ($label === $targetEntityLabel || false === strpos($label, '.'))
                    ) {
                        $data['entity']['label'] = $targetEntityPluralLabel;
                        $hasChanges              = true;
                    }
                }
            }

            if ($hasChanges) {
                $query  = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
                $params = ['data' => $data, 'id' => $config['id']];
                $types  = ['data' => 'array', 'id' => 'integer'];
                $this->logQuery($logger, $query, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $params, $types);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array [class_name => ['label' => label, 'plural_label' => plural_label], ...]
     */
    protected function loadEntityLabels(LoggerInterface $logger)
    {
        $sql = 'SELECT class_name, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $result = [];

        $rows = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');

            $result[$row['class_name']] = [
                'label'        => isset($data['entity']['label']) ? $data['entity']['label'] : null,
                'plural_label' => isset($data['entity']['plural_label']) ? $data['entity']['plural_label'] : null
            ];
        }

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     *  [
     *      ['id' => config_id, 'class' => class_name, 'field' => field_name, 'type' => field_type, 'data' => data]
     *      , ...
     *  ]
     */
    protected function loadAssociationFieldConfigs(LoggerInterface $logger)
    {
        $sql    = 'SELECT fc.id, ec.class_name, fc.field_name, fc.type, fc.data'
            . ' FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE fc.type IN (:types)';
        $params = ['types' => [RelationType::MANY_TO_ONE, RelationType::MANY_TO_MANY]];
        $types  = ['types' => Connection::PARAM_STR_ARRAY];
        $this->logQuery($logger, $sql, $params, $types);

        $result = [];

        $rows = $this->connection->fetchAll($sql, $params, $types);
        foreach ($rows as $row) {
            $result[] = [
                'id'    => $row['id'],
                'class' => $row['class_name'],
                'field' => $row['field_name'],
                'type'  => $row['type'],
                'data'  => $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }

        return $result;
    }
}
