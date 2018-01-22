<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Configuration;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;

class AttributeImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    const ATTRIBUTE_IMPORT_FROM_CSV_JOB_NAME = 'attribute_import_from_csv';

    /**
     * {@inheritDoc}
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => FieldConfigModel::class,
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => self::ATTRIBUTE_IMPORT_FROM_CSV_JOB_NAME,
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_entity_config_entity_field.add_or_replace',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_JOB_NAME => 'entity_export_template_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                'oro_entity_config_attribute.export_template',
        ]);
    }
}
