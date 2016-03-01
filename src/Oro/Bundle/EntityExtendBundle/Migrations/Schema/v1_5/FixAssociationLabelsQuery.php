<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Connection;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $entityConfigs = $this->loadEntityLabels($logger);
        // fix entity labels if needed
        $entityConfigs = $this->fixEntityLabels($entityConfigs, $logger, $dryRun);

        // fix field labels if needed
        $fieldConfigs = $this->loadAssociationFieldConfigs($logger);
        foreach ($fieldConfigs as $fieldConfig) {
            $data = $fieldConfig['data'];
            if (empty($data['extend']['target_entity'])) {
                continue;
            }

            $className               = $fieldConfig['class'];
            $fieldName               = $fieldConfig['field'];
            $fieldType               = $fieldConfig['type'];
            $label                   = $data['entity']['label'];
            $description             = $data['entity']['description'];
            $targetClassName         = $data['extend']['target_entity'];
            $targetEntityLabel       = isset($entityConfigs[$targetClassName])
                ? $entityConfigs[$targetClassName]['label']
                : null;
            $targetEntityPluralLabel = isset($entityConfigs[$targetClassName])
                ? $entityConfigs[$targetClassName]['plural_label']
                : null;

            $hasChanges = false;
            // fix field label if needed
            if ($fieldType === RelationType::MANY_TO_ONE) {
                if ($targetEntityLabel
                    && $label !== $targetEntityLabel
                    && (
                        $label === $this->getLabel('label', $targetClassName, $fieldName)
                        || false === strpos($label, '.')
                    )
                ) {
                    $data['entity']['label'] = $targetEntityLabel;
                    $hasChanges              = true;
                }
            } elseif ($fieldType === RelationType::MANY_TO_MANY) {
                if ($targetEntityLabel
                    && $targetEntityPluralLabel
                    && $label !== $targetEntityPluralLabel
                    && (
                        $label === $targetEntityLabel
                        || $label === $this->getLabel('label', $targetClassName, $fieldName)
                        || false === strpos($label, '.')
                    )
                ) {
                    $data['entity']['label'] = $targetEntityPluralLabel;
                    $hasChanges              = true;
                }
            }
            // fix field description if needed
            if ($description === $this->getLabel('description', $targetClassName, $fieldName)) {
                $data['entity']['description'] = $this->getLabel('description', $className, $fieldName);
                $hasChanges                    = true;
            }

            if ($hasChanges) {
                $query  = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
                $params = ['data' => $data, 'id' => $fieldConfig['id']];
                $types  = ['data' => 'array', 'id' => 'integer'];
                $this->logQuery($logger, $query, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $params, $types);
                }
            }
        }
    }

    /**
     * @param array           $entityConfigs
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function fixEntityLabels(array $entityConfigs, LoggerInterface $logger, $dryRun = false)
    {
        foreach ($entityConfigs as $className => &$entityConfig) {
            $data = $entityConfig['data'];
            if (!isset($data['extend']['owner']) || $data['extend']['owner'] !== ExtendScope::OWNER_SYSTEM) {
                continue;
            }

            $hasChanges = false;
            if ($entityConfig['label'] !== $this->getLabel('label', $className)
                && false === strpos($entityConfig['label'], '.')
            ) {
                $entityConfig['label']   = $this->getLabel('label', $className);
                $data['entity']['label'] = $entityConfig['label'];
                $hasChanges              = true;
            }
            if ($entityConfig['plural_label'] !== $this->getLabel('plural_label', $className)
                && false === strpos($entityConfig['plural_label'], '.')
            ) {
                $entityConfig['plural_label']   = $this->getLabel('plural_label', $className);
                $data['entity']['plural_label'] = $entityConfig['plural_label'];
                $hasChanges                     = true;
            }
            if ($entityConfig['description'] !== $this->getLabel('description', $className)
                && false === strpos($entityConfig['description'], '.')
            ) {
                $entityConfig['description']   = $this->getLabel('description', $className);
                $data['entity']['description'] = $entityConfig['description'];
                $hasChanges                    = true;
            }
            if ($hasChanges) {
                $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
                $params = ['data' => $data, 'id' => $entityConfig['id']];
                $types  = ['data' => 'array', 'id' => 'integer'];
                $this->logQuery($logger, $query, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $params, $types);
                }
            }
        }

        return $entityConfigs;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     *  [
     *      class_name => [
     *          'id'           => config_id,
     *          'label'        => label,
     *          'plural_label' => plural_label,
     *          'description'  => description,
     *          'data'         => data
     *      ]
     *      , ...
     *  ]
     */
    protected function loadEntityLabels(LoggerInterface $logger)
    {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $result = [];

        $rows = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');

            $result[$row['class_name']] = [
                'id'           => $row['id'],
                'label'        => isset($data['entity']['label']) ? $data['entity']['label'] : null,
                'plural_label' => isset($data['entity']['plural_label']) ? $data['entity']['plural_label'] : null,
                'description'  => isset($data['entity']['description']) ? $data['entity']['description'] : null,
                'data'         => $data
            ];
        }

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     *  [
     *      [
     *          'id'    => config_id,
     *          'class' => class_name,
     *          'field' => field_name,
     *          'type'  => field_type,
     *          'data'  => data
     *      ]
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

    /**
     * @param string      $labelKey
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return string
     */
    protected function getLabel($labelKey, $className, $fieldName = null)
    {
        return ConfigHelper::getTranslationKey('entity', $labelKey, $className, $fieldName);
    }
}
