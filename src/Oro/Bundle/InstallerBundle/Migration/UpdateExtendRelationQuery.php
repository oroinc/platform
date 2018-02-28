<?php

namespace Oro\Bundle\InstallerBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateExtendRelationQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $entityFrom;

    /**
     * @var string
     */
    private $entityTo;

    /**
     * @var string
     */
    private $relationFrom;

    /**
     * @var string
     */
    private $relationTo;

    /**
     * @var string
     */
    private $relationType;

    /**
     * @param string $entityFrom
     * @param string $entityTo
     * @param string $relationFrom
     * @param string $relationTo
     * @param string $relationType
     */
    public function __construct($entityFrom, $entityTo, $relationFrom, $relationTo, $relationType)
    {
        $this->entityFrom = $entityFrom;
        $this->entityTo = $entityTo;
        $this->relationFrom = $relationFrom;
        $this->relationTo = $relationTo;
        $this->relationType = $relationType;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->processQueries($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $row = $this->connection->fetchAssoc(
            'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1',
            [$this->entityFrom]
        );
        if ($row) {
            $id = $row['id'];
            $data = $this->connection->convertToPHPValue($row['data'], 'array');

            $hasChanges = false;
            $relationKeyFrom = $this->buildRelationKey($this->relationFrom);
            if (isset($data['extend']['relation'][$relationKeyFrom])) {
                $relationKeyTo = $this->buildRelationKey($this->relationTo);
                $data['extend']['relation'][$relationKeyTo] =
                    $data['extend']['relation'][$relationKeyFrom];
                unset($data['extend']['relation'][$relationKeyFrom]);

                if (isset($data['extend']['relation'][$relationKeyTo]['field_id'])) {
                    /** @var FieldConfigId $fieldId */
                    $fieldId = $data['extend']['relation'][$relationKeyTo]['field_id'];
                    $reflectionProperty = new \ReflectionProperty(get_class($fieldId), 'fieldName');
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($fieldId, $this->relationTo);
                    $data['extend']['relation'][$relationKeyTo]['field_id'] = $fieldId;
                }

                $hasChanges = true;
            }
            if (isset($data['extend']['schema']['relation'][$this->relationFrom])) {
                $data['extend']['schema']['relation'][$this->relationTo] =
                    $data['extend']['schema']['relation'][$this->relationFrom];
                unset($data['extend']['schema']['relation'][$this->relationFrom]);

                $hasChanges = true;
            }
            if (isset($data['extend']['schema']['addremove'][$this->relationFrom])) {
                $data['extend']['schema']['addremove'][$this->relationTo] =
                    $data['extend']['schema']['addremove'][$this->relationFrom];
                unset($data['extend']['schema']['addremove'][$this->relationFrom]);

                $hasChanges = true;
            }

            if ($hasChanges) {
                $this->updateEntityConfig($id, $data, $logger, $dryRun);
            }
            $this->updateEntityFieldConfig($id, $logger, $dryRun);
        }
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    private function buildRelationKey($fieldName)
    {
        return ExtendHelper::buildRelationKey(
            $this->entityFrom,
            $fieldName,
            $this->relationType,
            $this->entityTo
        );
    }

    /**
     * @param int             $id
     * @param array           $data
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function updateEntityConfig($id, array $data, LoggerInterface $logger, $dryRun)
    {
        $query = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
        $params = ['data' => $data, 'id' => $id];
        $types = ['data' => 'array', 'id' => 'integer'];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }

    /**
     * @param int             $entityId
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function updateEntityFieldConfig($entityId, LoggerInterface $logger, $dryRun)
    {
        $query = 'UPDATE oro_entity_config_field SET field_name = :newField'
            . ' WHERE entity_id = :entityId and field_name = :oldField';
        $params = ['oldField' => $this->relationFrom, 'newField' => $this->relationTo, 'entityId' => $entityId];
        $types = ['oldField' => 'string', 'newField' => 'string', 'entityId' => 'integer'];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }
}
