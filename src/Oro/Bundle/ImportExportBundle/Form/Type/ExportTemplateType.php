<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ImportExportBundle\Form\Model\ExportTemplateData;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ExportTemplateType extends ExportType
{
    const NAME = 'oro_importexport_export_template';

    /**
     * @param string $entityName
     * @return array
     */
    protected function getExportProcessorsChoices($entityName)
    {
        $aliases = $this->processorRegistry->getProcessorAliasesByEntity(
            ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
            $entityName
        );
        $result = array();
        foreach ($aliases as $alias) {
            $result[$alias] = $this->generateProcessorLabel($alias);
        }

        return $result;
    }

    /**
     * @param string $alias
     * @return string
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
        return self::NAME;
    }
}
