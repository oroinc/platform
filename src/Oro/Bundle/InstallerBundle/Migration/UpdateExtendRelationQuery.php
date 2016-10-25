<?php

namespace Oro\Bundle\InstallerBundle\Migration;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

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
            $originalData = $row['data'];
            $originalData = $originalData ? $this->connection->convertToPHPValue($originalData, Type::TARRAY) : [];

            $data = $originalData;
            $fullRelationFrom = implode(
                '|',
                [$this->relationType, $this->entityFrom, $this->entityTo, $this->relationFrom]
            );
            $fullRelationTo = implode(
                '|',
                [$this->relationType, $this->entityFrom, $this->entityTo, $this->relationTo]
            );
            if (isset($data['extend']['relation'][$fullRelationFrom])) {
                $data['extend']['relation'][$fullRelationTo] =
                    $data['extend']['relation'][$fullRelationFrom];
                unset($data['extend']['relation'][$fullRelationFrom]);

                if (isset($data['extend']['relation'][$fullRelationTo]['field_id'])) {
                    /** @var FieldConfigId $fieldId */
                    $fieldId = $data['extend']['relation'][$fullRelationTo]['field_id'];
                    $reflectionProperty = new \ReflectionProperty(get_class($fieldId), 'fieldName');
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($fieldId, $this->relationTo);
                    $data['extend']['relation'][$fullRelationTo]['field_id'] = $fieldId;
                }
            }
            if (isset($data['extend']['schema']['relation'][$this->relationFrom])) {
                $data['extend']['schema']['relation'][$this->relationTo] =
                    $data['extend']['schema']['relation'][$this->relationFrom];
                unset($data['extend']['schema']['relation'][$this->relationFrom]);
            }
            if (isset($data['extend']['schema']['addremove'][$this->relationFrom])) {
                $data['extend']['schema']['addremove'][$this->relationTo] =
                    $data['extend']['schema']['addremove'][$this->relationFrom];
                unset($data['extend']['schema']['addremove'][$this->relationFrom]);
            }

            if ($data !== $originalData) {
                $query = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
                $parameters = [$this->connection->convertToDatabaseValue($data, Type::TARRAY), $id];

                $this->logQuery($logger, $query, $parameters);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $parameters);
                }
            }

            $query = 'UPDATE oro_entity_config_field SET field_name = ? WHERE entity_id = ? and field_name = ?';
            $parameters = [$this->relationTo, $id, $this->relationFrom];
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $parameters);
            }
        }
    }
}
