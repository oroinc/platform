<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ExportTemplateType extends ExportType
{
    const NAME = 'oro_importexport_export_template';

    /**
     * {@inheritdoc}
     */
    protected function getProcessorType()
    {
        return ProcessorRegistry::TYPE_EXPORT_TEMPLATE;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateProcessorLabel($alias)
    {
        return sprintf('oro.importexport.export_template.%s', $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
