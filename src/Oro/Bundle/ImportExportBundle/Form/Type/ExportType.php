<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ImportExportBundle\Form\Model\ExportData;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ExportType extends AbstractType
{
    const NAME = 'oro_importexport_export';

    /**
     * @var ProcessorRegistry
     */
    protected $processorRegistry;

    /**
     * @param ProcessorRegistry $processorRegistry
     */
    public function __construct(ProcessorRegistry $processorRegistry)
    {
        $this->processorRegistry = $processorRegistry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'processorAlias',
            'choice',
            array(
                'label' => 'oro.importexport.export.processor',
                'choices' => $this->getExportProcessorsChoices($options['entityName']),
                'required' => true,
                'placeholder' => false,
            )
        );
    }

    /**
     * @param string $entityName
     * @return array
     */
    protected function getExportProcessorsChoices($entityName)
    {
        $aliases = $this->processorRegistry->getProcessorAliasesByEntity(
            ProcessorRegistry::TYPE_EXPORT,
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
        return sprintf('oro.importexport.export.%s', $alias);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => ExportData::class,
            )
        );
        $resolver->setRequired(array('entityName'));
        $resolver->setAllowedTypes(
            array(
                'entityName' => 'string'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
