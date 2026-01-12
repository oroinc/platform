<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Configuration;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;

/**
 * Provides import/export configuration for field configuration models.
 *
 * This provider defines the import and export jobs and processors used for handling field configuration
 * data in CSV format, enabling administrators to bulk import field configurations and export templates
 * for field configuration management.
 */
class FieldConfigModelImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    #[\Override]
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => FieldConfigModel::class,
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'entity_fields_import_from_csv',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_entity_config_entity_field.add_or_replace',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_JOB_NAME => 'entity_export_template_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                'oro_entity_config_entity_field.export_template',
        ]);
    }
}
