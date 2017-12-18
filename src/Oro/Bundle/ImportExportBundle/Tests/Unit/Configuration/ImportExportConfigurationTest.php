<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use PHPUnit\Framework\TestCase;

class ImportExportConfigurationTest extends TestCase
{
    public function testGetters()
    {
        $parameters = $this->getConfigurationParameters();
        $configuration = new ImportExportConfiguration($parameters);

        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_ROUTE_OPTIONS],
            $configuration->getRouteOptions()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_ENTITY_CLASS],
            $configuration->getEntityClass()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_FILE_PREFIX],
            $configuration->getFilePrefix()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_EXPORT_JOB_NAME],
            $configuration->getExportJobName()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS],
            $configuration->getExportProcessorAlias()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL],
            $configuration->getExportButtonLabel()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_EXPORT_POPUP_TITLE],
            $configuration->getExportPopupTitle()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_JOB_NAME],
            $configuration->getExportTemplateJobName()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS],
            $configuration->getExportTemplateProcessorAlias()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_IMPORT_JOB_NAME],
            $configuration->getImportJobName()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS],
            $configuration->getImportProcessorAlias()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_IMPORT_VALIDATION_JOB_NAME],
            $configuration->getImportValidationJobName()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL],
            $configuration->getImportValidationButtonLabel()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL],
            $configuration->getImportEntityLabel()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_IMPORT_STRATEGY_TOOLTIP],
            $configuration->getImportStrategyTooltip()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_IMPORT_PROCESSORS_TO_CONFIRMATION_MESSAGE],
            $configuration->getImportProcessorsToConfirmationMessage()
        );
        static::assertSame(
            $parameters[ImportExportConfiguration::FIELD_IMPORT_ADDITIONAL_NOTICES],
            $configuration->getImportAdditionalNotices()
        );
    }

    /**
     * @return array
     */
    protected function getConfigurationParameters(): array
    {
        return [
            ImportExportConfiguration::FIELD_ROUTE_OPTIONS => ['option1', 'option2'],
            ImportExportConfiguration::FIELD_ENTITY_CLASS => 'entity',
            ImportExportConfiguration::FIELD_FILE_PREFIX => 'file',
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'export job',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'export processor',
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL => 'export label',
            ImportExportConfiguration::FIELD_EXPORT_POPUP_TITLE => 'export title',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_JOB_NAME => 'export t job',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'export t processor',
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'import job',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'import processor',
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_JOB_NAME => 'import v job',
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL => 'import v label',
            ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL => 'Some Entity',
            ImportExportConfiguration::FIELD_IMPORT_STRATEGY_TOOLTIP => 'tooltip',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSORS_TO_CONFIRMATION_MESSAGE => ['key' => 'value'],
            ImportExportConfiguration::FIELD_IMPORT_ADDITIONAL_NOTICES => ['value1','value2']
        ];
    }
}
