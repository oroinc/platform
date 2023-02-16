<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\EntityConfigBundle\Async\Topic\AttributePreImportTopic;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Configuration\AttributeImportExportConfigurationProvider;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration as Config;

class AttributeImportExportConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        self::assertEquals(
            new Config([
                Config::FIELD_ENTITY_CLASS => FieldConfigModel::class,
                Config::FIELD_IMPORT_JOB_NAME => 'attribute_import_from_csv',
                Config::FIELD_IMPORT_VALIDATION_JOB_NAME => 'attribute_import_validation_from_csv',
                Config::FIELD_IMPORT_PROCESSOR_ALIAS =>
                    'oro_entity_config_entity_field.add_or_replace',
                Config::FIELD_EXPORT_TEMPLATE_JOB_NAME => 'entity_export_template_to_csv',
                Config::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                    'oro_entity_config_attribute.export_template',
                Config::FIELD_IMPORT_PROCESSOR_TOPIC_NAME => AttributePreImportTopic::getName(),
            ]),
            (new AttributeImportExportConfigurationProvider())->get()
        );
    }
}
