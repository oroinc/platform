<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Configuration\FieldConfigModelImportExportConfigurationProvider;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use PHPUnit\Framework\TestCase;

class FieldConfigModelImportExportConfigurationProviderTest extends TestCase
{
    public function testGet()
    {
        static::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => FieldConfigModel::class,
                ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'entity_fields_import_from_csv',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS =>
                    'oro_entity_config_entity_field.add_or_replace',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_JOB_NAME => 'entity_export_template_to_csv',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                    'oro_entity_config_entity_field.export_template',
            ]),
            (new FieldConfigModelImportExportConfigurationProvider())->get()
        );
    }
}
