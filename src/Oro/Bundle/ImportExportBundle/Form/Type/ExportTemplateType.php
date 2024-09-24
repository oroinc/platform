<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ExportTemplateType extends ExportType
{
    const NAME = 'oro_importexport_export_template';

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
