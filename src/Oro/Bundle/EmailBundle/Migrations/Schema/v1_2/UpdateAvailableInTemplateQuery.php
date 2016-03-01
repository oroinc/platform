<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_2;

use Psr\Log\LoggerInterface;

use Metadata\MetadataFactory;

use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

/**
 * Sets value of email.available_in_template attribute to TRUE for all fields
 * except a value of this attribute is changed by an user
 * and has own @ConfigField annotations with email.available_in_template attribute
 */
class UpdateAvailableInTemplateQuery extends ParametrizedMigrationQuery
{
    protected static $allowedTypes = [
        'string'    => true,
        'integer'   => true,
        'smallint'  => true,
        'bigint'    => true,
        'boolean'   => true,
        'decimal'   => true,
        'datetime'  => true,
        'date'      => true,
        'time'      => true,
        'text'      => true,
        'float'     => true,
        'money'     => true,
        'percent'   => true,
        'file'      => true,
        'image'     => true,
        'ref-one'   => true,
        'manyToOne' => true,
    ];

    /** @var MetadataFactory */
    protected $metadataFactory;

    /**
     * @param MetadataFactory $metadataFactory
     */
    public function __construct(MetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
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
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $classNames = $this->getAllConfigurableEntities($logger);
        foreach ($classNames as $className) {
            if (!class_exists($className)) {
                // skip if a class no longer exists
                continue;
            }
            $fieldConfigs        = $this->loadFieldConfigs($logger, $className);
            $fieldsChangedByUser = $this->loadFieldsChangedByUser($logger, $className);
            /** @var EntityMetadata $metadata */
            $metadata = $this->metadataFactory->getMetadataForClass($className);
            foreach ($fieldConfigs as $fieldName => $fieldConfig) {
                if (isset($fieldsChangedByUser[$fieldName])) {
                    // skip because the value is changed by an user
                    continue;
                }
                if (!isset(self::$allowedTypes[$fieldConfig['type']])) {
                    // skip because this data type should not be available in email templates
                    continue;
                }
                if ($metadata && $metadata->configurable && isset($metadata->propertyMetadata[$fieldName])) {
                    /** @var FieldMetadata $fieldMetadata */
                    $fieldMetadata = $metadata->propertyMetadata[$fieldName];
                    if (isset($fieldMetadata->defaultValues['email'])
                        && array_key_exists('available_in_template', $fieldMetadata->defaultValues['email'])) {
                        // skip because this field has @ConfigField annotations with email.available_in_template
                        continue;
                    }
                }
                $data = $fieldConfig['data'];
                if (isset($data['email']['available_in_template']) && $data['email']['available_in_template']) {
                    // skip because the value is already TRUE
                    continue;
                }

                if (!isset($data['email'])) {
                    $data['email'] = [];
                }
                $data['email']['available_in_template'] = true;

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
     * @param LoggerInterface $logger
     *
     * @return string[]
     */
    protected function getAllConfigurableEntities(LoggerInterface $logger)
    {
        $sql = 'SELECT class_name FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $result = [];
        $rows   = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $result[] = $row['class_name'];
        }

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $className
     *
     * @return array
     */
    protected function loadFieldConfigs(LoggerInterface $logger, $className)
    {
        $sql    = 'SELECT fc.id, fc.type, fc.field_name, fc.data'
            . ' FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = :class';
        $params = ['class' => $className];
        $types  = ['class' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $result = [];

        $rows = $this->connection->fetchAll($sql, $params, $types);
        foreach ($rows as $row) {
            $fieldName          = $row['field_name'];
            $result[$fieldName] = [
                'id'   => $row['id'],
                'type' => $row['type'],
                'data' => $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $className
     *
     * @return string[]
     */
    protected function loadFieldsChangedByUser(LoggerInterface $logger, $className)
    {
        $sql    = 'SELECT field_name, diff'
            . ' FROM oro_entity_config_log_diff'
            . ' WHERE class_name = :class AND scope = :scope AND field_name IS NOT NULL';
        $params = ['class' => $className, 'scope' => 'email'];
        $types  = ['class' => 'string', 'scope' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $result = [];
        $rows   = $this->connection->fetchAll($sql, $params, $types);
        foreach ($rows as $row) {
            $diff = $this->connection->convertToPHPValue($row['diff'], 'array');
            if (isset($diff['available_in_template'])) {
                $fieldName = $row['field_name'];
                if (!isset($result[$fieldName])) {
                    $result[$fieldName] = $fieldName;
                }
            }
        }

        return $result;
    }
}
