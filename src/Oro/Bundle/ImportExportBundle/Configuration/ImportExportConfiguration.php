<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Import/Export configuration holder.
 */
class ImportExportConfiguration extends ParameterBag implements ImportExportConfigurationInterface
{
    public const FIELD_ROUTE_OPTIONS = 'routeOptions';
    public const FIELD_ENTITY_CLASS = 'entityClass';
    public const FIELD_FILE_PREFIX = 'filePrefix';

    public const FIELD_FEATURE_NAME = 'featureName';

    public const FIELD_EXPORT_JOB_NAME = 'exportJobName';
    public const FIELD_EXPORT_PROCESSOR_ALIAS = 'exportProcessorAlias';
    public const FIELD_EXPORT_BUTTON_LABEL = 'exportButtonLabel';
    public const FIELD_EXPORT_POPUP_TITLE = 'exportPopupTitle';

    public const FIELD_EXPORT_TEMPLATE_JOB_NAME = 'exportTemplateJobName';
    public const FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS = 'exportTemplateProcessorAlias';
    public const FIELD_EXPORT_TEMPLATE_BUTTON_LABEL = 'exportTemplateButtonLabel';

    public const FIELD_IMPORT_JOB_NAME = 'importJobName';
    public const FIELD_IMPORT_PROCESSOR_ALIAS = 'importProcessorAlias';

    public const FIELD_IMPORT_ENTITY_LABEL = 'importEntityLabel';
    public const FIELD_IMPORT_STRATEGY_TOOLTIP = 'importStrategyTooltip';
    public const FIELD_IMPORT_PROCESSORS_TO_CONFIRMATION_MESSAGE = 'importProcessorToConfirmationMessages';
    public const FIELD_IMPORT_VALIDATION_JOB_NAME = 'importValidationJobName';
    public const FIELD_IMPORT_VALIDATION_BUTTON_LABEL = 'importValidationButtonLabel';
    public const FIELD_IMPORT_ADDITIONAL_NOTICES = 'importAdditionalNotices';
    public const FIELD_IMPORT_PROCESSOR_TOPIC_NAME = 'importProcessorTopicName';

    public const FIELD_IMPORT_COLUMNS_NOTICE = 'importColumnsNotice';
    public const FIELD_IMPORT_FORM_FILE_ACCEPT_ATTRIBUTE = 'fileAcceptAttribute';
    public const FIELD_IMPORT_FORM_FILE_MIME_TYPES = 'fileMimeTypes';

    #[\Override]
    public function getRouteOptions(): array
    {
        return $this->get(self::FIELD_ROUTE_OPTIONS, []);
    }

    #[\Override]
    public function getEntityClass(): string
    {
        return $this->get(self::FIELD_ENTITY_CLASS, '');
    }

    #[\Override]
    public function getFilePrefix()
    {
        return $this->get(self::FIELD_FILE_PREFIX);
    }

    #[\Override]
    public function getExportJobName()
    {
        return $this->get(self::FIELD_EXPORT_JOB_NAME);
    }

    #[\Override]
    public function getExportProcessorAlias()
    {
        return $this->get(self::FIELD_EXPORT_PROCESSOR_ALIAS);
    }

    #[\Override]
    public function getExportButtonLabel()
    {
        return $this->get(self::FIELD_EXPORT_BUTTON_LABEL);
    }

    #[\Override]
    public function getExportPopupTitle()
    {
        return $this->get(self::FIELD_EXPORT_POPUP_TITLE);
    }

    #[\Override]
    public function getExportTemplateJobName()
    {
        return $this->get(self::FIELD_EXPORT_TEMPLATE_JOB_NAME);
    }

    #[\Override]
    public function getExportTemplateProcessorAlias()
    {
        return $this->get(self::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS);
    }

    #[\Override]
    public function getImportJobName()
    {
        return $this->get(self::FIELD_IMPORT_JOB_NAME);
    }

    #[\Override]
    public function getImportProcessorAlias()
    {
        return $this->get(self::FIELD_IMPORT_PROCESSOR_ALIAS);
    }

    #[\Override]
    public function getImportEntityLabel()
    {
        return $this->get(self::FIELD_IMPORT_ENTITY_LABEL);
    }

    #[\Override]
    public function getImportValidationJobName()
    {
        return $this->get(self::FIELD_IMPORT_VALIDATION_JOB_NAME);
    }

    #[\Override]
    public function getImportValidationButtonLabel()
    {
        return $this->get(self::FIELD_IMPORT_VALIDATION_BUTTON_LABEL);
    }

    #[\Override]
    public function getImportStrategyTooltip()
    {
        return $this->get(self::FIELD_IMPORT_STRATEGY_TOOLTIP);
    }

    #[\Override]
    public function getImportProcessorsToConfirmationMessage(): array
    {
        return $this->get(self::FIELD_IMPORT_PROCESSORS_TO_CONFIRMATION_MESSAGE, []);
    }

    #[\Override]
    public function getImportAdditionalNotices(): array
    {
        return $this->get(self::FIELD_IMPORT_ADDITIONAL_NOTICES, []);
    }

    public function getImportProcessorTopicName(): ?string
    {
        return $this->get(self::FIELD_IMPORT_PROCESSOR_TOPIC_NAME);
    }

    #[\Override]
    public function getImportColumnsNotice(): ?string
    {
        return $this->get(self::FIELD_IMPORT_COLUMNS_NOTICE);
    }

    #[\Override]
    public function getImportFileAcceptAttribute(): ?string
    {
        return $this->get(self::FIELD_IMPORT_FORM_FILE_ACCEPT_ATTRIBUTE);
    }

    #[\Override]
    public function getImportFileMimeTypes(): ?array
    {
        return $this->get(self::FIELD_IMPORT_FORM_FILE_MIME_TYPES);
    }

    #[\Override]
    public function getFeatureName(): ?string
    {
        return $this->get(self::FIELD_FEATURE_NAME);
    }
}
