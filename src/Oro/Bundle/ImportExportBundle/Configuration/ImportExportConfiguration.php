<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

use Symfony\Component\HttpFoundation\ParameterBag;

class ImportExportConfiguration extends ParameterBag implements ImportExportConfigurationInterface
{
    const FIELD_REFRESH_PAGE_ON_SUCCESS = 'refreshPageOnSuccess';
    const FIELD_AFTER_REFRESH_PAGE_MESSAGE = 'afterRefreshPageMessage';
    const FIELD_DATA_GRID_NAME = 'dataGridName';
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
    const FIELD_EXPORT_TEMPLATE_POPUP_TITLE = 'exportTemplatePopupTitle';

    const FIELD_IMPORT_JOB_NAME = 'importJobName';
    const FIELD_IMPORT_PROCESSOR_ALIAS = 'importProcessorAlias';
    const FIELD_IMPORT_BUTTON_LABEL = 'importButtonLabel';
    const FIELD_IMPORT_POPUP_TITLE = 'importPopupTitle';

    const FIELD_IMPORT_VALIDATION_JOB_NAME = 'importValidationJobName';
    const FIELD_IMPORT_VALIDATION_BUTTON_LABEL = 'importValidationButtonLabel';

    /**
     * {@inheritDoc}
     */
    public function isRefreshPageOnSuccess(): bool
    {
        return $this->get(self::FIELD_REFRESH_PAGE_ON_SUCCESS, false);
    }

    /**
     * {@inheritDoc}
     */
    public function getAfterRefreshPageMessage()
    {
        return $this->get(self::FIELD_AFTER_REFRESH_PAGE_MESSAGE);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataGridName()
    {
        return $this->get(self::FIELD_DATA_GRID_NAME);
    }

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
    public function getExportTemplateButtonLabel()
    {
        return $this->get(self::FIELD_EXPORT_TEMPLATE_BUTTON_LABEL);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportTemplatePopupTitle()
    {
        return $this->get(self::FIELD_EXPORT_TEMPLATE_POPUP_TITLE);
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
    public function getImportButtonLabel()
    {
        return $this->get(self::FIELD_IMPORT_BUTTON_LABEL);
    }

    /**
     * {@inheritDoc}
     */
    public function getImportPopupTitle()
    {
        return $this->get(self::FIELD_IMPORT_POPUP_TITLE);
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
}
