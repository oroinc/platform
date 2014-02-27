<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendSchemaGenerator;

class UpdateExtendConfigMigrationQuery implements MigrationQuery
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var ExtendSchemaGenerator
     */
    protected $schemaGenerator;

    /**
     * @var ExtendConfigDumper
     */
    protected $configDumper;

    public function __construct($options, ExtendSchemaGenerator $schemaGenerator, ExtendConfigDumper $configDumper)
    {
        $this->options         = $options;
        $this->schemaGenerator = $schemaGenerator;
        $this->configDumper    = $configDumper;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $result = [];
        foreach ($this->options as $entityClassName => $entityOptions) {
            if (isset($entityOptions['configs'])) {
                $result[] = sprintf(
                    'CREATE EXTEND ENTITY %s',
                    $entityClassName
                );
            }
            if (isset($entityOptions['fields'])) {
                foreach ($entityOptions['fields'] as $fieldName => $fieldOptions) {
                    $result[] = sprintf(
                        'CREATE EXTEND FIELD %s FOR %s',
                        $fieldName,
                        $entityClassName
                    );
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->schemaGenerator->parseConfigs($this->options);
        $this->configDumper->updateConfig();
        $this->configDumper->dump();
    }
}
