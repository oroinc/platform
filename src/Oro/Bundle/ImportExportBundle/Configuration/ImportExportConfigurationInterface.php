<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

interface ImportExportConfigurationInterface
{
    /**
     * @return array
     */
    public function getRouteOptions(): array;

    /**
     * @return string
     */
    public function getEntityClass(): string;

    /**
     * @return string|null
     */
    public function getFilePrefix();

    /**
     * @return string|null
     */
    public function getExportJobName();

    /**
     * @return string|null
     */
    public function getExportProcessorAlias();

    /**
     * @return string|null
     */
    public function getExportButtonLabel();

    /**
     * @return string|null
     */
    public function getExportPopupTitle();

    /**
     * @return string|null
     */
    public function getExportTemplateJobName();

    /**
     * @return string|null
     */
    public function getExportTemplateProcessorAlias();

    /**
     * @return string|null
     */
    public function getImportJobName();

    /**
     * @return string|null
     */
    public function getImportProcessorAlias();

    /**
     * @return string|null
     */
    public function getImportValidationJobName();

    /**
     * @return string|null
     */
    public function getImportEntityLabel();

    /**
     * @return string|null
     */
    public function getImportStrategyTooltip();

    /**
     * @return string|null
     */
    public function getImportValidationButtonLabel();

    /**
     * @return string[]
     */
    public function getImportProcessorsToConfirmationMessage(): array;

    /**
     * @return string[]
     */
    public function getImportAdditionalNotices(): array;
}
