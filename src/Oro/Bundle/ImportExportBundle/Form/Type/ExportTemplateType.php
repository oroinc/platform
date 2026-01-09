<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

/**
 * Form type for selecting an export template processor.
 *
 * This form type extends {@see ExportType} to provide a specialized form for selecting
 * export template processors. It uses the `export_template` processor type and
 * generates translation keys specific to export templates, allowing users to
 * download template files for import.
 */
class ExportTemplateType extends ExportType
{
    public const NAME = 'oro_importexport_export_template';

    #[\Override]
    protected function getProcessorType()
    {
        return ProcessorRegistry::TYPE_EXPORT_TEMPLATE;
    }

    #[\Override]
    protected function generateProcessorLabel($alias)
    {
        return sprintf('oro.importexport.export_template.%s', $alias);
    }

    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
