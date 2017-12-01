<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

use Symfony\Component\HttpFoundation\ParameterBag;

class ImportExportConfiguration extends ParameterBag implements ImportExportConfigurationInterface
{
    const FIELD_ROUTE_OPTIONS = 'routeOptions';
    const FIELD_ENTITY_CLASS = 'entityClass';
    const FIELD_FILE_PREFIX = 'filePrefix';

    const FIELD_EXPORT_JOB_NAME = 'exportJobName';
    const FIELD_EXPORT_PROCESSOR_ALIAS = 'exportProcessorAlias';
    const FIELD_EXPORT_BUTTON_LABEL = 'exportButtonLabel';
    const FIELD_EXPORT_POPUP_TITLE = 'exportPopupTitle';

    const FIELD_EXPORT_TEMPLATE_JOB_NAME = 'exportTemplateJobName';
    const FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS = 'exportTemplateProcessorAlias';
    const FIELD_EXPORT_TEMPLATE_BUTTON_LABEL = 'exportTemplateButtonLabel';

    const FIELD_IMPORT_JOB_NAME = 'importJobName';
    const FIELD_IMPORT_PROCESSOR_ALIAS = 'importProcessorAlias';

    const FIELD_IMPORT_ENTITY_LABEL = 'importEntityLabel';
    const FIELD_IMPORT_STRATEGY_TOOLTIP = 'importStrategyTooltip';
    const FIELD_IMPORT_PROCESSORS_TO_CONFIRMATION_MESSAGE = 'importProcessorToConfirmationMessages';
    const FIELD_IMPORT_VALIDATION_JOB_NAME = 'importValidationJobName';
    const FIELD_IMPORT_VALIDATION_BUTTON_LABEL = 'importValidationButtonLabel';
    const FIELD_IMPORT_ADDITIONAL_NOTICES = 'importAdditionalNotices';

    /**
     * {@inheritDoc}
     */
    public function getRouteOptions(): array
    {
        return $this->get(self::FIELD_ROUTE_OPTIONS, []);
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass(): string
    {
        return $this->get(self::FIELD_ENTITY_CLASS, '');
    }

    /**
     * {@inheritDoc}
     */
    public function getFilePrefix()
    {
        return $this->get(self::FIELD_FILE_PREFIX);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportJobName()
    {
        return $this->get(self::FIELD_EXPORT_JOB_NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportProcessorAlias()
    {
        return $this->get(self::FIELD_EXPORT_PROCESSOR_ALIAS);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportButtonLabel()
    {
        return $this->get(self::FIELD_EXPORT_BUTTON_LABEL);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportPopupTitle()
    {
        return $this->get(self::FIELD_EXPORT_POPUP_TITLE);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportTemplateJobName()
    {
        return $this->get(self::FIELD_EXPORT_TEMPLATE_JOB_NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportTemplateProcessorAlias()
    {
        return $this->get(self::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportJobName()
    {
        return $this->get(self::FIELD_IMPORT_JOB_NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportProcessorAlias()
    {
        return $this->get(self::FIELD_IMPORT_PROCESSOR_ALIAS);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportEntityLabel()
    {
        return $this->get(self::FIELD_IMPORT_ENTITY_LABEL);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportValidationJobName()
    {
        return $this->get(self::FIELD_IMPORT_VALIDATION_JOB_NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportValidationButtonLabel()
    {
        return $this->get(self::FIELD_IMPORT_VALIDATION_BUTTON_LABEL);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportStrategyTooltip()
    {
        return $this->get(self::FIELD_IMPORT_STRATEGY_TOOLTIP);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportProcessorsToConfirmationMessage(): array
    {
        return $this->get(self::FIELD_IMPORT_PROCESSORS_TO_CONFIRMATION_MESSAGE, []);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportAdditionalNotices(): array
    {
        return $this->get(self::FIELD_IMPORT_ADDITIONAL_NOTICES, []);
    }
}
