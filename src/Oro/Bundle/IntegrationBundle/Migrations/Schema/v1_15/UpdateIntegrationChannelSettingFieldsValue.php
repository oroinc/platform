<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL92Platform;

use Psr\Log\LoggerInterface;

use Oro\Component\Config\Common\ConfigObject;

use Oro\Bundle\EntityBundle\DBAL\Types\ConfigObjectType;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateIntegrationChannelSettingFieldsValue extends ParametrizedMigrationQuery
{
    protected $fields = [
        'synchronization_settings',
        'mapping_settings'
    ];

    protected $oldObjectClassNamesReplacement = [
        'O:43:"Oro\Bundle\DataGridBundle\Common\DataObject"',
        'O:39:"Oro\Bundle\DataGridBundle\Common\Object"'
    ];

    protected $newObjectClassNameReplacement = 'O:40:"Oro\Component\Config\Common\ConfigObject"';

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Convert columns "synchronization_settings" and "mapping_settings" ' .
        'from the type "object" to the type "config_object" ';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger, $dryRun = false)
    {
        $query = 'SELECT * FROM oro_integration_channel';

        $this->logQuery($logger, $query);
        $result = $this->connection->fetchAll($query);

        $oldType = Type::getType(Type::OBJECT);
        $newType = Type::getType(ConfigObjectType::TYPE);
        $platform = $this->connection->getDatabasePlatform();

        foreach ($result as $channel) {
            foreach ($this->fields as $fieldName) {
                $fixedValue = $this->fixedValue($channel[$fieldName]);
                /** @var ConfigObject $configObject */
                $configObject = $oldType->convertToPHPValue($fixedValue, $platform);
                $data = $newType->convertToDatabaseValue($configObject->toArray(), $platform);
                $channel[$fieldName] = $data;
            }

            $query = 'UPDATE oro_integration_channel SET synchronization_settings = :syncSettingsValue, ' .
                'mapping_settings = :mapSettingsValue WHERE id = :id';
            $params = [
                'syncSettingsValue'     => $channel['synchronization_settings'],
                'mapSettingsValue'      => $channel['mapping_settings'],
                'id'                    => $channel['id']
            ];
            $types  = [
                'id'    => 'integer',
                'syncSettingsValue' => ConfigObjectType::TYPE,
                'mapSettingsValue'  => ConfigObjectType::TYPE
            ];

            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $params, $types);
            }
        }

        if ($platform instanceof PostgreSQL92Platform) {
            $this->prepareColumnOnPostgreSQL92($logger, $dryRun);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function prepareColumnOnPostgreSQL92($logger, $dryRun)
    {
        $updateSql = '';
        $baseUpdateColumnTypeSql = 'ALTER TABLE oro_integration_channel ALTER COLUMN %s TYPE JSON USING %s::JSON;';
        $baseUpdateColumnCommentSql = 'COMMENT ON COLUMN oro_integration_channel.%s IS \'(DC2Type:config_object)\';';

        foreach ($this->fields as $fieldName) {
            $updateSql .= sprintf($baseUpdateColumnTypeSql, $fieldName, $fieldName);
            $updateSql .= sprintf($baseUpdateColumnCommentSql, $fieldName);
        }

        $this->logQuery($logger, $updateSql);
        if (!$dryRun) {
            $this->connection->executeUpdate($updateSql);
        }
    }

    /**
     * We moved class that keeps as serialized string in database to other bundle
     * that's why we do this replace
     *
     * @param string $oldValue
     *
     * @return string
     */
    protected function fixedValue($oldValue)
    {
        $encodedOldValue = base64_decode($oldValue);
        $newValue = str_replace(
            $this->oldObjectClassNamesReplacement,
            $this->newObjectClassNameReplacement,
            $encodedOldValue
        );
        return base64_encode($newValue);
    }
}
